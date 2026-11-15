<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case BranchManager = 'branch_manager';
    case Cashier = 'cashier';
    case WarehouseAdmin = 'warehouse_admin';
    case ServiceAdvisor = 'service_advisor';
    case Mechanic = 'mechanic';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin / Owner',
            self::BranchManager => 'Branch Manager',
            self::Cashier => 'Kasir / Front Desk',
            self::WarehouseAdmin => 'Kepala / Admin Gudang',
            self::ServiceAdvisor => 'Service Advisor (SA)',
            self::Mechanic => 'Mekanik',
        };
    }
}
