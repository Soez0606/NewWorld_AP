<?php
namespace App\Controller\Admin;

use App\Entity\Contracts;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ContractsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Contracts::class;
    }
}
