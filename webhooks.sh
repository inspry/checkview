#!/bin/bash

if [ $# -eq 0 ]; then
  echo "Usage: $0 <file_with_urls>"
  exit 1
fi

file = "$1"

if [ ! -f "$file" ]; then
  echo "Error: File '$file' not found."
  exit 1
fi

while IFS= read -r url; do
  if [ -z "$url" ]; then
    continue
  fi

  echo "Requesting: $url"
  response=$(curl -s -o /dev/null -w "%{http_code}" "$url")

  echo "HTTP status code: $response"
done < "$file"

echo "Done."
