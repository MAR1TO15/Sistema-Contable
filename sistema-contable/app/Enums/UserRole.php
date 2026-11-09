<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case AdminFirma = 'admin_firma';
    case Contador = 'contador';
}
