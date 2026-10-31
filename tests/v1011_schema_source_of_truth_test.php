<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';
require_once dirname(__DIR__).'/app/Core/SchemaRepair.php';

$schema=\App\Core\SchemaRepair::schemaDefinition();
expect_true(count($schema)>=39,'SchemaRepair must parse the full v10.1 schema.');
foreach(['users','admins','player_profiles','player_availabilities','availability_invites','site_settings','ads','amateur_player_ads','amateur_player_career_entries'] as $table){
    expect_true(isset($schema[$table]),"SchemaRepair parser missing {$table}.");
}
foreach(['phone','privacy_accepted_at','username','email_verified_at','last_seen_at'] as $column){
    expect_true(isset($schema['users']['columns'][$column]),"Runtime users column missing from source-of-truth parser: {$column}.");
}
foreach(['idx_ads_delivery','idx_ads_location','idx_ads_rotation'] as $index){
    expect_true(isset($schema['ads']['indexes'][$index]),"Ad index missing from schema repair parser: {$index}.");
}
echo "PASS v1011_schema_source_of_truth_test\n";
