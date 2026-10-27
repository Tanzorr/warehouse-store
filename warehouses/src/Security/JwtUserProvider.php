<?php

declare(strict_types=1);

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\User\PayloadAwareUserProviderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Builds the authenticated user straight from the JWT payload signed by Laravel.
 *
 * The `role` claim ("admin" | "user") is mapped to Symfony roles, so warehouse
 * commands can be guarded with ROLE_ADMIN in security.yaml without this service
 * ever depending on the shop database (bounded contexts stay isolated).
 */
final class JwtUserProvider implements PayloadAwareUserProviderInterface
{
    public function loadUserByIdentifierAndPayload(string $identifier, array $payload): UserInterface
    {
        return new JwtClaimUser(
            $identifier,
            $this->rolesFromPayload($payload),
            isset($payload['sub']) ? (int) $payload['sub'] : null,
        );
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // No payload available (e.g. refresh flows); grant the baseline role only.
        return new JwtClaimUser($identifier, ['ROLE_USER']);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof JwtClaimUser) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        // Stateless firewall: tokens are self-contained, nothing to reload.
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return JwtClaimUser::class === $class || is_subclass_of($class, JwtClaimUser::class);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return string[]
     */
    private function rolesFromPayload(array $payload): array
    {
        $roles = ['ROLE_USER'];

        if (($payload['role'] ?? null) === 'admin') {
            $roles[] = 'ROLE_ADMIN';
        }

        return $roles;
    }
}
