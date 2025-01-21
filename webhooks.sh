#!/bin/bash

# Echo webhooks
echo "$WP_PUSHER_WEBHOOKS"

# Read URLs from the environment variable, split by newlines
IFS=$'\n' read -rd '' -a urls <<< "$WP_PUSHER_WEBHOOKS"

# Loop through each URL
for url in "${urls[@]}"; do
  if [ -z "$url" ]; then
    continue
  fi

  echo "Requesting: $url"
  response=$(curl -s -o /dev/null -w "%{http_code}" -L "$url")

  if [ "$response" == "000" ]; then
    echo "Request to $url failed. Debugging with verbose output:"
    curl -v -L "$url"
  else
    echo "HTTP status code: $response"
  fi
done

echo "Done."
