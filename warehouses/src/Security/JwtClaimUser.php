<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Stateless user reconstructed purely from the claims of a JWT issued by the
 * Laravel shop back-end. The warehouse service has no users table, so identity
 * and authorisation are derived entirely from the verified token payload.
 */
final class JwtClaimUser implements UserInterface
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        private readonly string $email,
        private readonly array $roles,
        private readonly ?int $id = null,
    ) {
    }

    /**
     * Laravel user id carried in the JWT "sub" claim — used to stamp inventory
     * transactions with the operator who issued the command.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
    }
}
