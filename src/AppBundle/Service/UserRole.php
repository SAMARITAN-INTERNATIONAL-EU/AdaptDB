<?php
namespace AppBundle\Service;

/**
 * Enum for UserRole
 */
abstract class UserRole
{
    const SYSTEM_ADMIN = "ROLE_SYSTEM_ADMIN";
    const DATA_ADMIN = "ROLE_DATA_ADMIN";
    const RESCUE_WORKER = "ROLE_RESCUE_WORKER";

}