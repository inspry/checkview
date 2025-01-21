#!/bin/bash

file_url="https://gist.githubusercontent.com/grayson-inspry/90323beb0c4ec526285fd62a04c07c06/raw/e9fb187951fd914954f56abfe87499455c017e23/webhooks.txt"
temp_file=$(mktemp)

echo "Downloading file from $file_url..."
curl -s -o "$temp_file" "$file_url"

if [ $? -ne 0 ]; then
  echo "Error: Failed to download file."
  exit 1
fi

echo "File downloaded successfully."

while IFS= read -r url; do
  if [ -z "$url" ]; then
    continue
  fi

  echo "Requesting: $url"
  response=$(curl -s -o /dev/null -w "%{http_code}" "$url")
  echo "HTTP status code: $response"
done < "$temp_file"

rm -f "$temp_file"
echo "Done."
