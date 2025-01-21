#!/bin/bash

# Echo webhooks
echo "$WP_PUSHER_WEBHOOKS"

# Read URLs from the environment variable, split by newlines
IFS=$'\n' read -rd '' -a urls <<< "$WP_PUSHER_WEBHOOKS"

# Loop through each URL
for url in "${urls[@]}"; do
  # Trim leading and trailing whitespace
  url=$(echo "$url" | xargs)

  # Skip empty URLs
  if [ -z "$url" ]; then
    continue
  fi

  # Validate URL format
  if ! [[ "$url" =~ ^https?:// ]]; then
    echo "Invalid URL format: $url"
    continue
  fi

  echo "Requesting: $url"

  response=$(curl -s -o /dev/null -w "%{http_code}" "$url")
  echo "HTTP status code: $response"
done

echo "Done."
