<?php

namespace App\Traits;

use App\Enums\HostServer;

trait HasServerAssignment
{
    protected $server1Count;
    protected $server2Count;
    protected $server3Count;
    protected $server4Count;
    protected $server5Count;

    public function getServerWithLeastHosts()
    {
        $serverCounts = [
            HostServer::Server_1->value => $this->server1Count,
            HostServer::Server_2->value => $this->server2Count,
            HostServer::Server_3->value => $this->server3Count,
            HostServer::Server_4->value => $this->server4Count,
            HostServer::Server_5->value => $this->server5Count,
        ];

        // Find the server with the least count
        $minCount = min($serverCounts);
        $selectedServer = array_search($minCount, $serverCounts);

        // Increment the count for the selected server
        switch ($selectedServer) {
            case HostServer::Server_1->value:
                $this->server1Count++;
                break;
            case HostServer::Server_2->value:
                $this->server2Count++;
                break;
            case HostServer::Server_3->value:
                $this->server3Count++;
                break;
            case HostServer::Server_4->value:
                $this->server4Count++;
                break;
            case HostServer::Server_5->value:
                $this->server5Count++;
                break;
        }

        // Return the selected server as an enum
        return HostServer::from($selectedServer);
    }

}
