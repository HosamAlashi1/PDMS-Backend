<?php
namespace App\Enums;

enum DevicesStatus: int
{
    case Online = 1;
    case OfflineShortTerm = 2;
    case OfflineLongTerm = 3;
}
