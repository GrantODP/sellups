<?php
$config =  [
  'database' => [
    'host' => '',
    'user' => '',
    'password' => '',
    'port' => ,
    'dbname' => 'c2c',
  ],
  'llm' =>
  [
    'gemini' =>
    [
      'key' => '',
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
