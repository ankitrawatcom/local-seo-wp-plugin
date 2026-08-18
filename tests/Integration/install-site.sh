#!/usr/bin/env bash
# Run after `docker compose up -d` from tests/Integration.
# Does not run as part of PHPUnit. Do not treat success as a product test pass
# until the checklist in docs/INTEGRATION-TEST-PLAN.md is executed.

set -euo pipefail
cd "$(dirname "$0")"

compose() {
	docker compose --profile tools "$@"
}

echo "Waiting for WordPress..."
for i in $(seq 1 60); do
	if compose run --rm wpcli core is-installed >/dev/null 2>&1; then
		break
	fi
	if compose run --rm wpcli core install \
		--url="http://127.0.0.1:8088" \
		--title="Local SEO Integration" \
		--admin_user="admin" \
		--admin_password="admin" \
		--admin_email="integration@example.test" \
		--skip-email >/dev/null 2>&1; then
		break
	fi
	sleep 2
done

compose run --rm wpcli plugin activate local-seo-by-ankit-rawat

echo "Attempting WooCommerce install from wordpress.org (may fail offline)..."
if compose run --rm wpcli plugin install woocommerce --activate; then
	echo "WooCommerce installed."
else
	echo "WooCommerce could not be downloaded. Record as environment limitation."
fi

echo "Attempting Plugin Check install from wordpress.org (may fail offline)..."
if compose run --rm wpcli plugin install plugin-check --activate; then
	echo "Plugin Check installed."
else
	echo "Plugin Check could not be downloaded. Record as environment limitation."
fi

compose run --rm wpcli plugin list
echo "Site: http://127.0.0.1:8088/wp-admin/  (admin / admin)"
echo "Next: follow docs/INTEGRATION-TEST-PLAN.md. Do not mark Tested up to until that plan passes."
