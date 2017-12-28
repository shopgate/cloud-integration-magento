<?php

/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

namespace Shopgate\OAuth2\Storage;

class Pdo extends \OAuth2\Storage\Pdo
{
    const AUTH_TOKEN_EXPIRE_SECONDS = 30;
    const AUTH_TYPE_CHECKOUT        = 'checkout';
    const AUTH_TOKEN_LENGTH         = 40;

    /**
     * Allows to customize which tables to install
     *
     * @param string $dbName
     *
     * @return string
     */
    public function getBuildSql($dbName = 'not used')
    {
        $sql = '';
        if (!empty($this->config['client_table'])) {
            $sql .= "
                CREATE TABLE IF NOT EXISTS {$this->config['client_table']} (
                  client_id             VARCHAR(80)   NOT NULL,
                  client_secret         VARCHAR(80),
                  redirect_uri          VARCHAR(2000),
                  grant_types           VARCHAR(80),
                  scope                 VARCHAR(4000),
                  user_id               VARCHAR(80),
                  PRIMARY KEY (client_id)
                );";
        }

        if (!empty($this->config['access_token_table'])) {
            $sql .= "
                CREATE TABLE IF NOT EXISTS {$this->config['access_token_table']} (
                  access_token         VARCHAR(40)    NOT NULL,
                  client_id            VARCHAR(80)    NOT NULL,
                  user_id              VARCHAR(80),
                  expires              TIMESTAMP      NOT NULL,
                  scope                VARCHAR(4000),
                  PRIMARY KEY (access_token)
                );";
        }

        if (!empty($this->config['code_table'])) {
            $sql .= "
                CREATE TABLE IF NOT EXISTS {$this->config['code_table']} (
                  authorization_code  VARCHAR(40)    NOT NULL,
                  client_id           VARCHAR(80)    NOT NULL,
                  user_id             VARCHAR(80),
                  redirect_uri        VARCHAR(2000),
                  expires             TIMESTAMP      NOT NULL,
                  scope               VARCHAR(4000),
                  id_token            VARCHAR(1000),
                  PRIMARY KEY (authorization_code)
                );";
        }

        if (!empty($this->config['refresh_token_table'])) {
            $sql .= "
                CREATE TABLE IF NOT EXISTS {$this->config['refresh_token_table']} (
                  refresh_token       VARCHAR(40)    NOT NULL,
                  client_id           VARCHAR(80)    NOT NULL,
                  user_id             VARCHAR(80),
                  expires             TIMESTAMP      NOT NULL,
                  scope               VARCHAR(4000),
                  PRIMARY KEY (refresh_token)
                );";
        }

        if (!empty($this->config['user_table'])) {
            $sql .= "
                CREATE TABLE IF NOT EXISTS {$this->config['user_table']} (
                  username            VARCHAR(80),
                  password            VARCHAR(80),
                  first_name          VARCHAR(80),
                  last_name           VARCHAR(80),
                  email               VARCHAR(80),
                  email_verified      BOOLEAN,
                  scope               VARCHAR(4000)
                );";
        }

        if (!empty($this->config['scope_table'])) {
            $sql .= "
                CREATE TABLE IF NOT EXISTS {$this->config['scope_table']} (
                  scope               VARCHAR(80)  NOT NULL,
                  is_default          BOOLEAN,
                  PRIMARY KEY (scope)
                );";
        }

        if (!empty($this->config['jwt_table'])) {
            $sql .= "
                CREATE TABLE IF NOT EXISTS {$this->config['jwt_table']} (
                  client_id           VARCHAR(80)   NOT NULL,
                  subject             VARCHAR(80),
                  public_key          VARCHAR(2000) NOT NULL
                );";
        }

        if (!empty($this->config['jti_table'])) {
            $sql .= "
                CREATE TABLE IF NOT EXISTS {$this->config['jti_table']} (
                  issuer              VARCHAR(80)   NOT NULL,
                  subject             VARCHAR(80),
                  audience            VARCHAR(80),
                  expires             TIMESTAMP     NOT NULL,
                  jti                 VARCHAR(2000) NOT NULL
                );";
        }

        if (!empty($this->config['public_key_table'])) {
            $sql .= "
                CREATE TABLE IF NOT EXISTS {$this->config['public_key_table']} (
                  client_id            VARCHAR(80),
                  public_key           VARCHAR(2000),
                  private_key          VARCHAR(2000),
                  encryption_algorithm VARCHAR(100) DEFAULT 'RS256'
                );";
        }

        if (!empty($this->config['resource_auth_codes'])) {
            $sql .= "
                CREATE TABLE IF NOT EXISTS {$this->config['resource_auth_codes']} (
                  token         VARCHAR(40)    NOT NULL UNIQUE,
                  user_id       VARCHAR(80),
                  resource_id   VARCHAR(80)    NOT NULL,
                  expires       TIMESTAMP      NOT NULL,
                  type          VARCHAR(255),
                  PRIMARY KEY (token)
                );";
        }

        return $sql;
    }

    /**
     * @param string $token
     * @param string $type
     *
     * @return array | false
     */
    public function getAuthItemByTokenAndType($token, $type)
    {
        $stmt = $this->db->prepare(
            sprintf('SELECT * from %s where token = :token and type = :type', $this->config['resource_auth_codes'])
        );
        $stmt->execute(compact('token', 'type'));

        if ($item = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $item['is_expired'] = strtotime($item['expires']) < time();
        }

        return $item;
    }

    /**
     * @param string     $type
     * @param int        $resource_id
     * @param null | int $user_id
     *
     * @return mixed
     */
    public function createAuthItemByType($type, $resource_id, $user_id = null)
    {
        /** remove exist auth item */
        $this->unsetAuthItemByResourceIdAndType($resource_id, $type);

        $token   = substr(hash('sha512', md5(microtime()) . $type), 0, self::AUTH_TOKEN_LENGTH);
        $expires = date('Y-m-d H:i:s', time() + self::AUTH_TOKEN_EXPIRE_SECONDS);

        $stmt = $this->db->prepare(
            sprintf(
                'INSERT INTO %s (token, user_id, resource_id, type, expires) VALUES (:token, :user_id, :resource_id, :type, :expires)',
                $this->config['resource_auth_codes']
            )
        );

        $stmt->execute(compact('token', 'user_id', 'resource_id', 'type', 'expires'));

        return $this->getAuthItemByTokenAndType($token, $type);
    }

    /**
     * @param string $token
     *
     * @return bool
     */
    public function unsetAuthItemByToken($token)
    {
        $stmt = $this->db->prepare(
            sprintf('DELETE FROM %s WHERE token = :token', $this->config['resource_auth_codes'])
        );

        $stmt->execute(compact('token'));

        return $stmt->rowCount() > 0;
    }

    /**
     * @param int    $resource_id
     * @param string $type
     *
     * @return bool
     */
    public function unsetAuthItemByResourceIdAndType($resource_id, $type)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'DELETE FROM %s WHERE resource_id = :resource_id AND type = :type', $this->config['resource_auth_codes']
            )
        );

        $stmt->execute(compact('resource_id', 'type'));

        return $stmt->rowCount() > 0;
    }
}
