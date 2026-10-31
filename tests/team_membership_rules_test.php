<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\TeamMembership;

expect_same([], TeamMembership::validateSettings(['role'=>'vice_captain','jersey_number'=>'8'], false), 'Valid vice captain settings must pass.');
expect_same([], TeamMembership::validateSettings(['role'=>'player','jersey_number'=>''], false), 'Empty jersey number is allowed.');
expect_true(isset(TeamMembership::validateSettings(['role'=>'captain','jersey_number'=>'10'], false)['role']), 'Captain role cannot be assigned from member settings.');
expect_true(isset(TeamMembership::validateSettings(['role'=>'player','jersey_number'=>'101'], false)['jersey_number']), 'Jersey number above 99 must fail.');
expect_true(TeamMembership::canManageSportingRole('captain'), 'Captain has sporting management permission.');
expect_true(TeamMembership::canManageSportingRole('vice_captain'), 'Vice captain has sporting management permission.');
expect_true(!TeamMembership::canManageSportingRole('player'), 'Regular player lacks sporting management permission.');
expect_true(TeamMembership::canEditMemberSettings('captain', false), 'Captain can edit another member.');
expect_true(!TeamMembership::canEditMemberSettings('vice_captain', false), 'Vice captain cannot edit team roles.');
expect_true(TeamMembership::canEditMemberSettings('captain', true), 'Captain can edit own jersey settings.');
expect_same([], TeamMembership::validateSettings(['role'=>'captain','jersey_number'=>'1'], true), 'Captain can keep captain role while updating jersey.');
expect_true(isset(TeamMembership::validateSettings(['role'=>'player','jersey_number'=>'1'], true)['role']), 'Captain role cannot be downgraded through member settings.');

fwrite(STDOUT,"PASS team_membership_rules_test\n");
