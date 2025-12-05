<?php
namespace App\Controller\Admin;

use App\Entity\Roles;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class RolesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Roles::class;
    }
}