<?php


require_once './backend/config/Config.php';
require_once './backend/domain/ItemImage.php';
require_once './backend/core/Result.php';

class LlmRequester
{

  public static function prompt(string $prompt): Result
  {
    $data = [
      'contents' => [
        [
          'parts' => [
            ['text' => $prompt],
          ],
        ],
      ],
    ];

    $url = C2Config::get('llm', 'gemini')['url'];

    $data = json_encode($data);
    return self::send_payload($url, $data);
  }

  /**
   * @param Image[] $images Array of Image objects
   */
  public static function prompt_with_images(string $prompt, array $images): Result
  {
    $image_parts = [];
    foreach ($images as $image) {
      $image_parts[] = $image->inline_data();
    }

    $image_parts[] = ['text' => $prompt];
    $data = [
      'contents' => [
        [
          'parts' => $image_parts,
        ],
      ],
    ];


    $url = C2Config::get('llm', 'gemini')['url'];


    $data = json_encode($data);
    return self::send_payload($url, $data);
  }

  private static function send_payload($url, $payload): Result
  {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
    ]);
    $response =  curl_exec($ch);

    if (curl_errno($ch)) {
      return Result::Err(new InternalServerError('Curl Error:' . curl_errno($ch)));
    }
    $decoded = json_decode($response, true);
    $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (empty($text)) {
      return Result::Err(new InternalServerError('Error receving text from decoded response'));
    }
    return Result::Ok($text);
  }
}
