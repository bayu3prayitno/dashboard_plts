<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Alias node untuk firmware gateway (topik vpp/kampus/{nama})
    |--------------------------------------------------------------------------
    |
    | Firmware mengirim ke topik vpp/kampus/node1, node2, node3 tanpa node_id.
    | Kunci = nama di topik, nilai = node_id di database. Nama yang tidak ada di
    | daftar ini tetap diterima bila berupa angka (vpp/kampus/2 => node_id 2).
    |
    */

    'node_aliases' => [
        'node1' => 1,
        'node2' => 2,
        'node3' => 3,
    ],

];
