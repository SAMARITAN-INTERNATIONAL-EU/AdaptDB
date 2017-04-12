<?php
// src/AppBundle/Entity/User.php

namespace AppBundle\Entity;

use AppBundle\Service\UserRole;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entity User
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @ORM\Table(name="user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    protected $isDeleted = false;

    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    protected $locked = false;

    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * User Returns true if the user has only the role "ROLE_RESCUE_WORKER"
     * @return bool
     */
    public function hasOnlyRoleRescueWorker() {
        $hasRoleRescueWorker = false;
        $hasRoleDataAdmin = false;
        $hasRoleSystemAdmin = false;

        foreach ($this->getRoles() as $roleKey => $role) {
            if ($role == UserRole::RESCUE_WORKER) {
                $hasRoleRescueWorker = true;
            }
            if ($role == UserRole::DATA_ADMIN) {
                $hasRoleDataAdmin = true;
            }
            if ($role == UserRole::SYSTEM_ADMIN) {
                $hasRoleSystemAdmin = true;
            }
        }
        return ($hasRoleRescueWorker == true && $hasRoleDataAdmin == false && $hasRoleSystemAdmin == false);
    }

    /**
     * User Returns true if the user has the role "ROLE_SYSTEM_ADMIN"
     * @return bool
     */
    public function hasRoleSystemAdmin() {
        foreach ($this->getRoles() as $roleKey => $role) {
            if ($role == UserRole::SYSTEM_ADMIN) {
                return true;
            }
        }
        return false;
    }

    /**
     * User Returns true if the user has the role "ROLE_DATA_ADMIN"
     * @return bool
     */
    public function hasRoleDataAdmin() {
        foreach ($this->getRoles() as $roleKey => $role) {
            if ($role == UserRole::DATA_ADMIN) {
                return true;
            }
        }
        return false;
    }

    /**
     * User Returns true if the user has the role "ROLE_RESCUE_WORKER"
     * @return bool
     */
    public function hasRoleRescueWorker() {
        foreach ($this->getRoles() as $roleKey => $role) {
            if ($role == UserRole::RESCUE_WORKER) {
                return true;
            }
        }
        return false;
    }


    /**
     * Get isDeleted
     *
     * @return boolean
     */
    public function getIsDeleted()
    {
        return $this->isDeleted();
    }

    /**
     * Set isDeleted
     *
     * @param boolean isDeleted
     *
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * Set locked
     *
     * @param boolean $locked
     *
     * @return User
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked
     *
     * @return boolean
     */
    public function getLocked()
    {
        return $this->locked;
    }
}
