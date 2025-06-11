<?php
$config =  [
  'database' => [
    'host' => 'mysql-1b7f1ee8-grantoranduprez-de06.i.aivencloud.com',
    'user' => 'avnadmin',
    'password' => 'AVNS_pww21FkaqpGQcgixFkB',
    'port' => 15890,
    'dbname' => 'c2c',
  ],
  'llm' =>
  [
    'gemini' =>
    [
      'key' => 'AIzaSyA419SLbvV74Lz8_RqIwLhjexf35uws4Jg',
      'model' => 'gemini-2.0-flash',
      'url' => "https://generativelanguage.googleapis.com/v1beta/models/MODEL_PLACE:generateContent?key=API_PLACE",
    ],
  ]
];
$key = $config['llm']['gemini']['key'];
$model = $config['llm']['gemini']['model'];
$config['llm']['gemini']['url'] = str_replace('MODEL_PLACE', $model, $config['llm']['gemini']['url']);
$config['llm']['gemini']['url'] = str_replace('API_PLACE', $key, $config['llm']['gemini']['url']);

return $config;
