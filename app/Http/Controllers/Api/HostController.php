<?php

namespace App\Http\Controllers\Api;

use App\Enums\HostServer;
use App\Http\Controllers\Controller;
use App\Imports\HostsImport;
use App\Models\Hosts;
use App\Traits\ApiResponseTrait;
use App\Traits\HasServerAssignment;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HostController extends Controller
{
    private $serverCounts;

    use HasServerAssignment,ApiResponseTrait;

    public function __construct()
    {
        $this->server1Count = Hosts::where('server', HostServer::Server_1)->count();
        $this->server2Count = Hosts::where('server', HostServer::Server_2)->count();
        $this->server3Count = Hosts::where('server', HostServer::Server_3)->count();
        $this->server4Count = Hosts::where('server', HostServer::Server_4)->count();
        $this->server5Count = Hosts::where('server', HostServer::Server_5)->count();
    }

    public function list(Request $request)
    {
        try {
            $status = $request->input('status');
            $query = $request->input('q');
            $size = $request->input('size', 10);
            $page = $request->input('page', 1);
            $skip = ($page - 1) * $size;

            $hosts = Hosts::where('status', $status)
                ->when($query, fn($q) => $q->where('hostname', 'like', "%$query%"))
                ->skip($skip)->take($size)->get();

            $totalHosts = Hosts::where('status', $status)
                ->when($query, fn($q) => $q->where('hostname', 'like', "%$query%"))
                ->count();

            $totalPages = ceil($totalHosts / $size);

            $stats = [
                'working' => Hosts::where('status', 'working')->count(),
                'offLess24h' => Hosts::where('status', 'off_less_24h')->count(),
                'offMore24h' => Hosts::where('status', 'off_more_24h')->count(),
            ];

            return $this->successResponse([
                'hosts' => $hosts,
                'stats' => $stats,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'size' => $size,
                'total_count' => $totalHosts,
            ]);
        } catch (\Exception $ex) {
            return $this->errorResponse(400, $ex->getMessage());
        }
    }

    public function all(Request $request)
    {
        try {
            $status = $request->input('status');
            $hosts = Hosts::where('status', $status)->get();

            return $this->successResponse($hosts);
        } catch (\Exception $ex) {
            return $this->errorResponse(400, $ex->getMessage());
        }
    }

    public function add(Request $request)
    {
        try {
            $server = $this->getServerWithLeastHosts()->value;

            $host = Hosts::create([
                'lat' => $request->input('lat'),
                'lng' => $request->input('lng'),
                'hostname' => $request->input('hostname'),
                'host_ip' => $request->input('host_ip'),
                'group_id' => $request->input('group_id'),
                'group_title' => $request->input('group_title'),
                'server' => $server,
                'insert_type' => 'manually',
                'insert_user_id' => auth()->id(),
                'insert_user_name' => auth()->user()->name,
            ]);

            return $this->successResponse($host, true, "{$host->hostname} has been added successfully.");
        } catch (\Exception $ex) {
            return $this->errorResponse(400, $ex->getMessage());
        }
    }


    public function update($id, Request $request)
    {
        try {
            $host = Hosts::find($id);
            if (!$host) {
                return $this->successResponse(null,false, "Host not found.");
            }

            $host->update([
                'lat' => $request->input('lat'),
                'lng' => $request->input('lng'),
                'hostname' => $request->input('hostname'),
                'host_ip' => $request->input('host_ip'),
                'group_id' => $request->input('group_id'),
                'group_title' => $request->input('group_title'),
            ]);

            return $this->successResponse($host, true, "{$host->hostname} has been updated successfully.");
        } catch (\Exception $ex) {
            return $this->errorResponse(400, $ex->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $host = Hosts::find($id);
            if (!$host) {
                return $this->successResponse(null,false, "Host not found.");
            }

            $host->delete();

            return $this->successResponse(null, true, "{$host->hostname} has been deleted successfully.");
        } catch (\Exception $ex) {
            return $this->errorResponse(400, $ex->getMessage());
        }
    }

    public function import(Request $request)
    {
        try {
            $file = $request->file('file');
            $groupID = $request->input('group_id');
            $groupTitle = $request->input('group_title');

            Excel::import(new HostsImport($groupID, $groupTitle), $file);

            return $this->successResponse(null, true, "File has been imported successfully.");
        } catch (\Exception $ex) {
            return $this->errorResponse(400, $ex->getMessage());
        }
    }

//    private function getServerWithLeastHosts()
//    {
//        $minServer = array_keys($this->serverCounts, min($this->serverCounts))[0];
//        $this->serverCounts[$minServer]++;
//        return $minServer;
//    }
}
