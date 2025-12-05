<?php
namespace App\Controller\Admin;

use App\Entity\ProducersInfo;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ProducersInfoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProducersInfo::class;
    }
}