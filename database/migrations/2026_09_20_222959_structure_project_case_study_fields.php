<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Three structural changes a case study needs to work as a landing page.
 *
 * 1. The buyer-facing section becomes a structure instead of a paragraph.
 *    One block of prose asks the reader to find the answer; three labelled
 *    parts - the problem, the value, why the execution differed - answer
 *    the three questions a buyer actually has, in the order they ask them.
 *    `client_value` is replaced rather than kept: it was added in this same
 *    uncommitted round, so there is no history to preserve.
 *
 * 2. A project supports more than one service, and one of them is the
 *    reason the page exists. `is_primary` on the pivot records which,
 *    so the page, the schema and the internal links can lead with it
 *    instead of showing whichever row the database returned first.
 *
 * 3. Location fields, prepared and empty. The district is already the
 *    `area_id` relation (Areas are Riyadh's districts); city, neighbourhood
 *    and landmark are the parts that relation cannot hold. All nullable,
 *    all editable, none populated - a district we cannot evidence is a
 *    fabricated local signal, and this project does not ship those.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('client_problem')->nullable()->after('outcome');
            $table->text('client_benefit')->nullable()->after('client_problem');
            $table->text('execution_difference')->nullable()->after('client_benefit');
            $table->dropColumn('client_value');

            $table->string('city')->nullable()->after('area_id');
            $table->string('neighborhood')->nullable()->after('city');
            $table->string('landmark')->nullable()->after('neighborhood');
        });

        Schema::table('project_service', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('service_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('client_value')->nullable()->after('outcome');
            $table->dropColumn(['client_problem', 'client_benefit', 'execution_difference', 'city', 'neighborhood', 'landmark']);
        });

        Schema::table('project_service', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};
