<?php


function get_input_json(): ?array
{
  $data = null;
  if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $data = json_decode(file_get_contents('php://input'), true);
  } else {
    $data = $_POST;
  }
  return $data;
}

function sentence_case($string)
{
  $string = strtolower($string);
  return ucfirst($string);
}

function has_required_keys(?array $data, array $keys): bool
{
  if (!$data) {
    return false;
  }

  foreach ($keys as $key) {
    if (!array_key_exists($key, $data)) {
      return false;
    }
    if (empty($data[$key])) {
      return false;
    }
  }
  return true;
}

function gen_slug(string $title): string
{
  $slug = strtolower(trim($title));

  $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

  $slug = trim($slug, '-');

  return $slug;
}
