<?php

require_once('vendor/autoload.php');

use Aws\Translate\TranslateClient;

$client = new TranslateClient(['version' => 'latest', 'region' => 'eu-west-1',]);
$path = 'lang/';

$source = json_decode(file_get_contents($path.'en.json'), true);

$encode = fn($v) => preg_replace('/:(\w+)/', '{{$1}}', $v);
$decode = fn($v) => preg_replace('/{{(\w+)}}/', ':$1', $v);

foreach (['es', 'fr-CA'] as $lang) {
    $out = [];

    foreach ($source as $key => $value) {
        $out[$key] = $decode($client->translateText([
            'SourceLanguageCode' => 'en',
            'TargetLanguageCode' => $lang,
            'Text' => $encode($value),
        ])->get('TranslatedText'));
    }

    file_put_contents(
        $path.$lang.".json",
        json_encode($out, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
    );
}
