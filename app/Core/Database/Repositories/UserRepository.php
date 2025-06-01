<?php

namespace Flute\Core\Database\Repositories;

use Cycle\ORM\Select\Repository;
use Flute\Core\Database\Entities\User;
use Flute\Core\Exceptions\DuplicateEmailException;
use Flute\Core\Exceptions\DuplicateLoginException;
use Flute\Core\Exceptions\IncorrectPasswordException;
use Flute\Core\Exceptions\UserNotFoundException;
use Nette\Utils\Validators;

class UserRepository extends Repository
{
    /**
     * Method to find a user by login.
     *
     * @param string $login The login to search for.
     * @return User|null The found user or null if not found.
     */
    public function findByLogin(string $login)
    {
        return $this->select()->where('isTemporary', false)->where('login', $login)->fetchOne();
    }

    /**
     * Method to find a user by email.
     *
     * @param string $email The email to search for.
     * @return User|null The found user or null if not found.
     */
    public function findByEmail(string $email)
    {
        return $this->select()->where('isTemporary', false)->where('email', $email)->fetchOne();
    }

    /**
     * Check if the login or email is already in use
     *
     * @param string $param The login or email to check.
     * @throws DuplicateEmailException|DuplicateLoginException If the login or email is duplicated.
     */
    public function checkDuplicity(string $param)
    {
        $validate = $this->isEmail($param);

        $user = $this->getByEmailOrLogin($param);

        if ($user) {
            throw $validate
                ? new DuplicateEmailException($param)
                : new DuplicateLoginException($param);
        }
    }

    /**
     * Method to get user either by email or login.
     * 
     * @param string $loginOrEmail The login or email to search for.
     * @return User|null The found user or null if not found.
     */
    public function getByEmailOrLogin(string $loginOrEmail)
    {
        return $this->isEmail($loginOrEmail)
            ? $this->findByEmail($loginOrEmail)
            : $this->findByLogin($loginOrEmail);
    }

    /**
     * Check if the provided password is correct for the user.
     *
     * @param int $id The user ID.
     * @param string $password The password to check.
     * @return bool Returns true if the password is correct, false otherwise.
     * @throws UserNotFoundException If the user is not found.
     * @throws IncorrectPasswordException If the password is incorrect.
     */
    public function checkUserPassword(int $id, string $password)
    {
        $user = $this->findByPK($id);

        if (!$user) {
            throw new UserNotFoundException($id);
        }

        return $this->checkUserPasswordHash($password, $user['password']);
    }

    /**
     * Verifies if the given password matches the hashed password.
     * 
     * @param string $password The user's plaintext password
     * @param string $hash The hashed password
     * @return bool Returns true if the password matches the hash, false otherwise.
     * @throws IncorrectPasswordException If the password is incorrect.
     */
    public function checkUserPasswordHash(string $password, string $hash)
    {
        if (!password_verify($password, $hash)) {
            throw new IncorrectPasswordException($password);
        }

        return true;
    }

    /**
     * Method to check if a string is a valid email format.
     * 
     * @param string $email The string to check.
     * @return bool Returns true if the string is a valid email format, false otherwise.
     */
    protected function isEmail(string $email) : bool
    {
        return Validators::isEmail($email);
    }

    public function getLatestUsers(int $limit = 10) : array
    {
        return $this
            ->select()
            ->where('createdAt', '>=', (new \DateTimeImmutable())->modify('-7 day'))
            ->orderBy(['createdAt' => 'DESC'])
            ->limit($limit)
            ->fetchAll();
    }

    public function getOnlineUsers() : array
    {
        return $this
            ->select()
            ->where('last_logged', '>=', (new \DateTimeImmutable())->modify('-10 minutes'))
            ->fetchAll();
    }

    public function getTodayUsers() : array
    {
        $startOfDay = new \DateTimeImmutable('today');
        return $this
            ->select()
            ->where('last_logged', '>=', $startOfDay)
            ->fetchAll();
    }

    public function select(): \Cycle\ORM\Select
    {
        return parent::select()->where('isTemporary', false);
    }
}
