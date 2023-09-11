#!/bin/sh

bin/console cache:clear --quiet

composer dump-env prod --quiet
rm -f .env.prod.local

bin/console messenger:consume scheduler_default --limit=250 -vv
