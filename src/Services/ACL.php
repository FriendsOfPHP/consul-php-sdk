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

    public function bootstrap(array $bootstrap = []): ConsulResponse
    {
        // Consul expects a JSON object, even when empty
        return $this->client->put('/v1/acl/bootstrap', ['json' => (object) $bootstrap]);
    }

    public function replication(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/replication', ['query' => OptionsResolver::resolve($options, ['dc', 'consistent'])]);
    }

    public function login(array $login): ConsulResponse
    {
        return $this->client->post('/v1/acl/login', ['json' => $login]);
    }

    /**
     * Destroys the token (its SecretID) created via login().
     */
    public function logout(string $token): ConsulResponse
    {
        return $this->client->post('/v1/acl/logout', ['headers' => ['X-Consul-Token' => $token]]);
    }

    public function createToken(array $token = []): ConsulResponse
    {
        // Consul expects a JSON object, even when empty
        return $this->client->put('/v1/acl/token', ['json' => (object) $token]);
    }

    public function readToken(string $accessorId, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/token/'.$accessorId, ['query' => OptionsResolver::resolve($options, ['expanded', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function readSelfToken(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/token/self', ['query' => OptionsResolver::resolve($options, ['index', 'wait', 'stale', 'consistent'])]);
    }

    public function updateToken(string $accessorId, array $token): ConsulResponse
    {
        return $this->client->put('/v1/acl/token/'.$accessorId, ['json' => $token]);
    }

    public function cloneToken(string $accessorId, array $token = []): ConsulResponse
    {
        // Consul expects a JSON object, even when empty
        return $this->client->put('/v1/acl/token/'.$accessorId.'/clone', ['json' => (object) $token]);
    }

    public function deleteToken(string $accessorId): ConsulResponse
    {
        return $this->client->delete('/v1/acl/token/'.$accessorId);
    }

    public function listTokens(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/tokens', ['query' => OptionsResolver::resolve($options, ['policy', 'role', 'servicename', 'authmethod', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function createPolicy(array $policy): ConsulResponse
    {
        return $this->client->put('/v1/acl/policy', ['json' => $policy]);
    }

    public function readPolicy(string $id, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/policy/'.$id, ['query' => OptionsResolver::resolve($options, ['index', 'wait', 'stale', 'consistent'])]);
    }

    public function readPolicyByName(string $name, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/policy/name/'.$name, ['query' => OptionsResolver::resolve($options, ['index', 'wait', 'stale', 'consistent'])]);
    }

    public function updatePolicy(string $id, array $policy): ConsulResponse
    {
        return $this->client->put('/v1/acl/policy/'.$id, ['json' => $policy]);
    }

    public function deletePolicy(string $id): ConsulResponse
    {
        return $this->client->delete('/v1/acl/policy/'.$id);
    }

    public function listPolicies(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/policies', ['query' => OptionsResolver::resolve($options, ['index', 'wait', 'stale', 'consistent'])]);
    }

    public function createRole(array $role): ConsulResponse
    {
        return $this->client->put('/v1/acl/role', ['json' => $role]);
    }

    public function readRole(string $id, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/role/'.$id, ['query' => OptionsResolver::resolve($options, ['index', 'wait', 'stale', 'consistent'])]);
    }

    public function readRoleByName(string $name, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/role/name/'.$name, ['query' => OptionsResolver::resolve($options, ['index', 'wait', 'stale', 'consistent'])]);
    }

    public function updateRole(string $id, array $role): ConsulResponse
    {
        return $this->client->put('/v1/acl/role/'.$id, ['json' => $role]);
    }

    public function deleteRole(string $id): ConsulResponse
    {
        return $this->client->delete('/v1/acl/role/'.$id);
    }

    public function listRoles(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/roles', ['query' => OptionsResolver::resolve($options, ['policy', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function createAuthMethod(array $authMethod): ConsulResponse
    {
        return $this->client->put('/v1/acl/auth-method', ['json' => $authMethod]);
    }

    public function readAuthMethod(string $name, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/auth-method/'.$name, ['query' => OptionsResolver::resolve($options, ['index', 'wait', 'stale', 'consistent'])]);
    }

    public function updateAuthMethod(string $name, array $authMethod): ConsulResponse
    {
        return $this->client->put('/v1/acl/auth-method/'.$name, ['json' => $authMethod]);
    }

    public function deleteAuthMethod(string $name): ConsulResponse
    {
        return $this->client->delete('/v1/acl/auth-method/'.$name);
    }

    public function listAuthMethods(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/auth-methods', ['query' => OptionsResolver::resolve($options, ['index', 'wait', 'stale', 'consistent'])]);
    }

    public function createBindingRule(array $bindingRule): ConsulResponse
    {
        return $this->client->put('/v1/acl/binding-rule', ['json' => $bindingRule]);
    }

    public function readBindingRule(string $id, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/binding-rule/'.$id, ['query' => OptionsResolver::resolve($options, ['index', 'wait', 'stale', 'consistent'])]);
    }

    public function updateBindingRule(string $id, array $bindingRule): ConsulResponse
    {
        return $this->client->put('/v1/acl/binding-rule/'.$id, ['json' => $bindingRule]);
    }

    public function deleteBindingRule(string $id): ConsulResponse
    {
        return $this->client->delete('/v1/acl/binding-rule/'.$id);
    }

    public function listBindingRules(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/acl/binding-rules', ['query' => OptionsResolver::resolve($options, ['authmethod', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function readTemplatedPolicy(string $name): ConsulResponse
    {
        return $this->client->get('/v1/acl/templated-policy/name/'.$name);
    }

    public function previewTemplatedPolicy(string $name, array $variables = []): ConsulResponse
    {
        // Consul expects a JSON object, even when empty
        return $this->client->post('/v1/acl/templated-policy/preview/'.$name, ['json' => (object) $variables]);
    }

    public function listTemplatedPolicies(): ConsulResponse
    {
        return $this->client->get('/v1/acl/templated-policies');
    }
}
