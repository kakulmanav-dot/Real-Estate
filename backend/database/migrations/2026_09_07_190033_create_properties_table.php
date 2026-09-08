<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('reference_number')->unique();
            $table->string('short_description', 500)->nullable();
            $table->text('description');
            $table->enum('purpose', ['sale', 'rent'])->index();
            $table->string('property_type')->index();
            $table->enum('status', ['draft', 'published', 'sold', 'rented', 'archived'])
                ->default('draft')
                ->index();
            $table->decimal('price', 14, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('price_period')->nullable();
            $table->string('address');
            $table->string('city')->index();
            $table->string('state')->nullable();
            $table->string('country')->default('United States');
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('bedrooms')->default(0)->index();
            $table->unsignedSmallInteger('bathrooms')->default(0);
            $table->unsignedSmallInteger('balconies')->nullable();
            $table->unsignedSmallInteger('parking_spaces')->nullable();
            $table->decimal('area', 12, 2);
            $table->string('area_unit')->default('sqft');
            $table->string('furnishing_status')->nullable();
            $table->unsignedSmallInteger('year_built')->nullable();
            $table->boolean('featured')->default(false)->index();
            $table->json('amenities')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['purpose', 'property_type']);
            $table->index('price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
