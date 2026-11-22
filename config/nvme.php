<?php

return [
    'ssh_host' => env('NVME_SSH_HOST', 'jhcpl.in'),
    'ssh_user' => env('NVME_SSH_USER', 'customer'),
    'ssh_pass' => env('NVME_SSH_PASS', ''),
    'db_name'  => env('NVME_DB_NAME',  'specialitycare'),
    'db_user'  => env('NVME_DB_USER',  'root'),
    'db_pass'  => env('NVME_DB_PASS',  ''),
    // Added 2026-08-22 for sg89: nvme was hardened 2026-08-15 to key-only
    // auth on port 44 (no more password auth on 22), so the sshpass/port-22
    // tunnel this file used to feed is broken against current nvme. These
    // two make the tunnel use key auth + the right port instead.
    'ssh_port' => env('NVME_SSH_PORT', 44),
    'ssh_key'  => env('NVME_SSH_KEY', null),
];
