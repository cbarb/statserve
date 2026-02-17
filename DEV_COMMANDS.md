# Dev Commands

## Startup

```bash
# Start Sail (Docker) + Vite dev server
npm run start

# Or separately:
./vendor/bin/sail up -d
./vendor/bin/sail npm run dev
```

## Database

```bash
# Run migrations
./vendor/bin/sail artisan migrate

# Fresh migrate + seed (destroys all data)
./vendor/bin/sail artisan migrate:fresh --seed

# Seed only (requires empty or compatible DB)
./vendor/bin/sail artisan db:seed
```

## Dev Testing Tools

```bash
# Fill a group's weekly matches to the max (5/5) to trigger the limit
./vendor/bin/sail artisan dev:max-matches {group_slug}

# Reset a group's weekly matches back to 0/5
./vendor/bin/sail artisan dev:reset-matches {group_slug}
```

## Build

```bash
# Dev server with HMR
npm run dev

# Production build
npm run build
```

## Useful Sail Shortcuts

```bash
# Artisan
./vendor/bin/sail artisan <command>

# Tinker (REPL)
./vendor/bin/sail artisan tinker

# Run tests
./vendor/bin/sail artisan test

# Clear all caches
./vendor/bin/sail artisan optimize:clear

# Queue worker (if using queues)
./vendor/bin/sail artisan queue:work
```
