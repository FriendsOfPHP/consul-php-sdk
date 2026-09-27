<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class ACL
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function bootstrap(array $bootstrap = [], array $options = []): ConsulResponse
    {
        $params = [
            // Consul expects a JSON object, even when empty
            'json' => (object) $bootstrap,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/acl/bootstrap', $params);
    }

    public function replication(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/replication', ['query' => OptionsResolver::resolve($options, ['dc', 'consistent'])]);
    }

    public function login(array $login, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $login,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->post('/v1/acl/login', $params);
    }

    /**
     * Destroys the token (its SecretID) created via login().
     */
    public function logout(string $token, array $options = []): ConsulResponse
    {
        $params = [
            'headers' => ['X-Consul-Token' => $token],
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->post('/v1/acl/logout', $params);
    }

    public function createToken(array $token = [], array $options = []): ConsulResponse
    {
        $params = [
            // Consul expects a JSON object, even when empty
            'json' => (object) $token,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/acl/token', $params);
    }

    public function readToken(string $accessorId, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/token/'.$accessorId, ['query' => OptionsResolver::resolve($options, ['dc', 'expanded', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function readSelfToken(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/token/self', ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function updateToken(string $accessorId, array $token, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $token,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/acl/token/'.$accessorId, $params);
    }

    public function cloneToken(string $accessorId, array $token = [], array $options = []): ConsulResponse
    {
        $params = [
            // Consul expects a JSON object, even when empty
            'json' => (object) $token,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/acl/token/'.$accessorId.'/clone', $params);
    }

    public function deleteToken(string $accessorId, array $options = []): ConsulResponse
    {
        return $this->client->delete('/v1/acl/token/'.$accessorId, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function listTokens(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/tokens', ['query' => OptionsResolver::resolve($options, ['dc', 'policy', 'role', 'servicename', 'authmethod', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function createPolicy(array $policy, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $policy,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/acl/policy', $params);
    }

    public function readPolicy(string $id, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/policy/'.$id, ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function readPolicyByName(string $name, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/policy/name/'.$name, ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function updatePolicy(string $id, array $policy, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $policy,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/acl/policy/'.$id, $params);
    }

    public function deletePolicy(string $id, array $options = []): ConsulResponse
    {
        return $this->client->delete('/v1/acl/policy/'.$id, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function listPolicies(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/policies', ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function createRole(array $role, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $role,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/acl/role', $params);
    }

    public function readRole(string $id, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/role/'.$id, ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function readRoleByName(string $name, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/role/name/'.$name, ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function updateRole(string $id, array $role, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $role,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/acl/role/'.$id, $params);
    }

    public function deleteRole(string $id, array $options = []): ConsulResponse
    {
        return $this->client->delete('/v1/acl/role/'.$id, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function listRoles(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/roles', ['query' => OptionsResolver::resolve($options, ['dc', 'policy', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function createAuthMethod(array $authMethod, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $authMethod,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/acl/auth-method', $params);
    }

    public function readAuthMethod(string $name, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/auth-method/'.$name, ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function updateAuthMethod(string $name, array $authMethod, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $authMethod,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/acl/auth-method/'.$name, $params);
    }

    public function deleteAuthMethod(string $name, array $options = []): ConsulResponse
    {
        return $this->client->delete('/v1/acl/auth-method/'.$name, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function listAuthMethods(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/auth-methods', ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function createBindingRule(array $bindingRule, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $bindingRule,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/acl/binding-rule', $params);
    }

    public function readBindingRule(string $id, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/binding-rule/'.$id, ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function updateBindingRule(string $id, array $bindingRule, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $bindingRule,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/acl/binding-rule/'.$id, $params);
    }

    public function deleteBindingRule(string $id, array $options = []): ConsulResponse
    {
        return $this->client->delete('/v1/acl/binding-rule/'.$id, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function listBindingRules(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/binding-rules', ['query' => OptionsResolver::resolve($options, ['dc', 'authmethod', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function readTemplatedPolicy(string $name, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/templated-policy/name/'.$name, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function previewTemplatedPolicy(string $name, array $variables = [], array $options = []): ConsulResponse
    {
        $params = [
            // Consul expects a JSON object, even when empty
            'json' => (object) $variables,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->post('/v1/acl/templated-policy/preview/'.$name, $params);
    }

    public function listTemplatedPolicies(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/templated-policies', ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }
}
