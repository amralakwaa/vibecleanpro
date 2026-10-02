<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The trust/credentials system: internal Vibe Clean Pro standards (VCP-*),
 * internal training records, and external licences/certifications held or
 * planned. Visibility is governed by status + is_public (see CredentialStatus
 * and the Credential model's scopePublic).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();

            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();

            // CredentialType / CredentialStatus backed enums.
            $table->string('credential_type');
            $table->string('status')->default('planned');

            // "Vibe Clean Pro" for internal standards; the real body for external.
            $table->string('issuer')->default('Vibe Clean Pro');

            // Internal document code (VCP-QMS-001) - unique so a QR/verify URL
            // resolves to exactly one document. External credentials use
            // credential_number instead (a registry number, never invented).
            $table->string('document_code')->nullable()->unique();
            $table->string('credential_number')->nullable();

            $table->string('version')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->date('review_at')->nullable();

            $table->string('verification_url')->nullable();
            $table->foreignId('document_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('icon')->nullable();

            $table->text('summary_ar')->nullable();
            $table->text('summary_en')->nullable();
            $table->text('scope')->nullable();
            // Full document body (purpose, responsibilities, procedures, review,
            // exceptions, approval ...) as rich HTML, for the web page + print.
            $table->longText('body_ar')->nullable();

            // Internal = issued by Vibe Clean Pro itself. External = a third
            // party. Internal may go public when Active; external only when
            // Verified (enforced in scopePublic, not just here).
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_public')->default(false);

            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'is_public']);
            $table->index('credential_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
