<?php

$sconfig=[
    'bitrix24.webhook'=>[
        'description'=>'FormIt hook for bitrix24'
    ]
];

foreach($sconfig?:[] as $snippet=>$options){
    $snippet_file=$config['component']['core'].'elements/snippets/'.$snippet.'.php';
    if(!file_exists($snippet_file))continue;
    $data['modSnippet'][$snippet]=[
        'fields'=>[
            'name' => $snippet,
            'description' => $options['description'],
            'snippet' => trim(str_replace(['<?php', '?>'], '', file_get_contents($snippet_file))),
            'source' => 1,
        ],
        'options'=>$config['data_options']['modSnippet'],
        'relations'=>[
            'modCategory'=>[
                'main'=>'Snippets'
            ]
        ]
    ];
}