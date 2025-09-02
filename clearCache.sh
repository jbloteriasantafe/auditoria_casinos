#!/bin/bash
php7.0 artisan cache:clear &
php7.0 artisan config:clear &
php7.0 artisan config:cache &
wait
