<?php

namespace App\Imports;

use App\Models\Hosts;
use App\Traits\HasServerAssignment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class HostsImport implements ToCollection
{
    use HasServerAssignment;
    private $groupID, $groupTitle;

    public function __construct($groupID, $groupTitle)
    {
        $this->groupID = $groupID;
        $this->groupTitle = $groupTitle;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows->skip(1) as $row) {
            $server = $this->getServerWithLeastHosts()->value;
            Hosts::create([
                'lat' => $row[0],
                'lng' => $row[1],
                'hostname' => $row[2],
                'host_ip' => $row[3],
                'group_id' => $this->groupID,
                'group_title' => $this->groupTitle,
                'server' => $server,
                'insert_type' => 'import_file',
                'insert_user_id' => auth()->id(),
                'insert_user_name' => auth()->user()->name,
            ]);
        }
    }
}
