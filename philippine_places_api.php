<?php
// Simple API for Philippine places (demo list)
header('Content-Type: application/json');



$places = [
    [
        "region" => "National Capital Region",
        "provinces" => [
            [
                "province" => "Metro Manila",
                "cities" => [
                    "Manila", "Quezon City", "Caloocan", "Las Piñas", "Makati", "Malabon", "Mandaluyong", "Marikina", "Muntinlupa", "Navotas", "Parañaque", "Pasay", "Pasig", "San Juan", "Taguig", "Valenzuela"
                ]
            ]
        ]
    ],
    [
        "region" => "Ilocos Region",
        "provinces" => [
            ["province" => "Ilocos Norte", "cities" => ["Laoag", "Batac"]],
            ["province" => "Ilocos Sur", "cities" => ["Vigan", "Candon"]],
            ["province" => "La Union", "cities" => ["San Fernando"]],
            ["province" => "Pangasinan", "cities" => ["Dagupan", "San Carlos", "Urdaneta", "Alaminos"]]
        ]
    ],
    [
        "region" => "Cagayan Valley",
        "provinces" => [
            ["province" => "Batanes", "cities" => ["Basco"]],
            ["province" => "Cagayan", "cities" => ["Tuguegarao"]],
            ["province" => "Isabela", "cities" => ["Ilagan", "Cauayan", "Santiago"]],
            ["province" => "Nueva Vizcaya", "cities" => ["Bayombong"]],
            ["province" => "Quirino", "cities" => ["Cabarroguis"]]
        ]
    ],
    [
        "region" => "Central Luzon",
        "provinces" => [
            ["province" => "Aurora", "cities" => ["Baler"]],
            ["province" => "Bataan", "cities" => ["Balanga"]],
            ["province" => "Bulacan", "cities" => ["Malolos"]],
            ["province" => "Nueva Ecija", "cities" => ["Cabanatuan", "Gapan", "San Jose", "Palayan"]],
            ["province" => "Pampanga", "cities" => ["Angeles", "San Fernando"]],
            ["province" => "Tarlac", "cities" => ["Tarlac City"]],
            ["province" => "Zambales", "cities" => ["Olongapo"]]
        ]
    ],
    [
        "region" => "CALABARZON",
        "provinces" => [
            ["province" => "Batangas", "cities" => ["Batangas City", "Lipa", "Tanauan"]],
            ["province" => "Cavite", "cities" => ["Tagaytay", "Trece Martires", "Dasmariñas"]],
            ["province" => "Laguna", "cities" => ["San Pablo", "Santa Rosa", "Biñan", "Calamba", "Cabuyao"]],
            ["province" => "Quezon", "cities" => ["Lucena", "Tayabas"]],
            ["province" => "Rizal", "cities" => ["Antipolo"]]
        ]
    ],
    [
        "region" => "MIMAROPA",
        "provinces" => [
            ["province" => "Marinduque", "cities" => ["Boac"]],
            ["province" => "Occidental Mindoro", "cities" => ["Mamburao"]],
            ["province" => "Oriental Mindoro", "cities" => ["Calapan"]],
            ["province" => "Palawan", "cities" => ["Puerto Princesa"]],
            ["province" => "Romblon", "cities" => ["Romblon"]]
        ]
    ],
    [
        "region" => "Bicol Region",
        "provinces" => [
            ["province" => "Albay", "cities" => ["Legazpi", "Tabaco", "Ligao"]],
            ["province" => "Camarines Norte", "cities" => ["Daet"]],
            ["province" => "Camarines Sur", "cities" => ["Naga", "Iriga"]],
            ["province" => "Catanduanes", "cities" => ["Virac"]],
            ["province" => "Masbate", "cities" => ["Masbate City"]],
            ["province" => "Sorsogon", "cities" => ["Sorsogon City"]]
        ]
    ],
    [
        "region" => "Western Visayas",
        "provinces" => [
            ["province" => "Aklan", "cities" => ["Kalibo"]],
            ["province" => "Antique", "cities" => ["San Jose"]],
            ["province" => "Capiz", "cities" => ["Roxas City"]],
            ["province" => "Guimaras", "cities" => ["Jordan"]],
            ["province" => "Iloilo", "cities" => ["Iloilo City", "Passi", "Bago"]],
            ["province" => "Negros Occidental", "cities" => ["Bacolod", "Bago", "Cadiz", "Escalante", "Himamaylan", "Kabankalan", "La Carlota", "Sagay", "San Carlos", "Silay", "Sipalay", "Talisay", "Victorias"]]
        ]
    ],
    [
        "region" => "Central Visayas",
        "provinces" => [
            ["province" => "Bohol", "cities" => ["Tagbilaran"]],
            ["province" => "Cebu", "cities" => ["Cebu City", "Mandaue", "Lapu-Lapu", "Toledo", "Talisay", "Danao", "Bogo", "Carcar", "Naga"]],
            ["province" => "Negros Oriental", "cities" => ["Dumaguete", "Bayawan", "Bais", "Canlaon", "Guihulngan", "Tanjay"]],
            ["province" => "Siquijor", "cities" => ["Siquijor"]]
        ]
    ],
    [
        "region" => "Eastern Visayas",
        "provinces" => [
            ["province" => "Biliran", "cities" => ["Naval"]],
            ["province" => "Eastern Samar", "cities" => ["Borongan"]],
            ["province" => "Leyte", "cities" => ["Tacloban", "Ormoc", "Baybay"]],
            ["province" => "Northern Samar", "cities" => ["Catarman"]],
            ["province" => "Samar", "cities" => ["Catbalogan", "Calbayog"]],
            ["province" => "Southern Leyte", "cities" => ["Maasin"]]
        ]
    ],
    [
        "region" => "Zamboanga Peninsula",
        "provinces" => [
            ["province" => "Zamboanga del Norte", "cities" => ["Dipolog", "Dapitan"]],
            ["province" => "Zamboanga del Sur", "cities" => ["Pagadian"]],
            ["province" => "Zamboanga Sibugay", "cities" => ["Ipil"]],
            ["province" => "Zamboanga City", "cities" => ["Zamboanga City"]]
        ]
    ],
    [
        "region" => "Northern Mindanao",
        "provinces" => [
            ["province" => "Bukidnon", "cities" => ["Malaybalay", "Valencia"]],
            ["province" => "Camiguin", "cities" => ["Mambajao"]],
            ["province" => "Lanao del Norte", "cities" => ["Iligan"]],
            ["province" => "Misamis Occidental", "cities" => ["Oroquieta", "Ozamis", "Tangub"]],
            ["province" => "Misamis Oriental", "cities" => ["Cagayan de Oro", "Gingoog"]]
        ]
    ],
    [
        "region" => "Davao Region",
        "provinces" => [
            ["province" => "Davao de Oro", "cities" => ["Nabunturan"]],
            ["province" => "Davao del Norte", "cities" => ["Tagum", "Panabo", "Samal"]],
            ["province" => "Davao del Sur", "cities" => ["Digos"]],
            ["province" => "Davao Occidental", "cities" => ["Malita"]],
            ["province" => "Davao Oriental", "cities" => ["Mati"]],
            ["province" => "Davao City", "cities" => ["Davao City"]]
        ]
    ],
    [
        "region" => "SOCCSKSARGEN",
        "provinces" => [
            ["province" => "Cotabato", "cities" => ["Kidapawan"]],
            ["province" => "Sarangani", "cities" => ["Alabel"]],
            ["province" => "South Cotabato", "cities" => ["Koronadal", "General Santos"]],
            ["province" => "Sultan Kudarat", "cities" => ["Isulan", "Tacurong"]]
        ]
    ],
    [
        "region" => "Caraga",
        "provinces" => [
            ["province" => "Agusan del Norte", "cities" => ["Butuan"]],
            ["province" => "Agusan del Sur", "cities" => ["Bayugan"]],
            ["province" => "Dinagat Islands", "cities" => ["San Jose"]],
            ["province" => "Surigao del Norte", "cities" => ["Surigao City"]],
            ["province" => "Surigao del Sur", "cities" => ["Bislig", "Tandag"]]
        ]
    ],
    [
        "region" => "Bangsamoro Autonomous Region",
        "provinces" => [
            ["province" => "Basilan", "cities" => ["Isabela City"]],
            ["province" => "Lanao del Sur", "cities" => ["Marawi"]],
            ["province" => "Maguindanao", "cities" => ["Cotabato City"]],
            ["province" => "Sulu", "cities" => ["Jolo"]],
            ["province" => "Tawi-Tawi", "cities" => ["Bongao"]]
        ]
    ]
    // Add more municipalities as needed for completeness
];

if (isset($_GET['search'])) {
    $search = strtolower(trim($_GET['search']));
    $filtered = [];
    foreach ($places as $region) {
        $regionMatch = strpos(strtolower($region['region']), $search) !== false;
        $provinces = [];
        foreach ($region['provinces'] as $province) {
            $provinceMatch = strpos(strtolower($province['province']), $search) !== false;
            $cities = array_filter($province['cities'], function($city) use ($search) {
                return strpos(strtolower($city), $search) !== false;
            });
            if ($provinceMatch || count($cities) > 0) {
                $provinces[] = [
                    'province' => $province['province'],
                    'cities' => $provinceMatch ? $province['cities'] : array_values($cities)
                ];
            }
        }
        if ($regionMatch || count($provinces) > 0) {
            $filtered[] = [
                'region' => $region['region'],
                'provinces' => $regionMatch ? $region['provinces'] : $provinces
            ];
        }
    }
    echo json_encode($filtered);
} else {
    echo json_encode($places);
}
?>
