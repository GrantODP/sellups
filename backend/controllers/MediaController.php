<?php
require_once './backend/domain/ItemImage.php';
require_once './backend/util/Util.php';



class MediaController
{

  public static function get($media_slug)
  {

    if (empty($media_slug)) {
      return Responder::bad_request("Missing post listing slug");
    }

    $path = "./media/$media_slug";
    if (!file_exists($path)) {
      return Responder::not_found("No media item named: $media_slug");
    }

    $mime = mime_content_type($path);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    readfile($path);
  }
}
