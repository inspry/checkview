#!/bin/bash

# Read URLs from the environment variable, split by newlines
IFS=',' read -ra entries <<< "$WP_PUSHER_WEBHOOKS"

# Loop through each URL
for entry in "${entries[@]}"; do
  # Trim leading and trailing whitespace
  url=$(echo "$entry" | xargs)

  # Skip empty URLs
  if [ -z "$entry" ]; then
    continue
  fi

  # Validate URL format
  if ! [[ "$entry" =~ ^https?:// ]]; then
    echo "Invalid URL format: $entry"
    continue
  fi

  echo "Requesting: $entry"

  response=$(curl -s -o /dev/null -w "%{http_code}" "$entry")
  echo "HTTP status code: $response"
done

echo "Done."
