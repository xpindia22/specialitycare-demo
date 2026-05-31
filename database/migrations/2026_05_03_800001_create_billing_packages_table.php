<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Package definitions
        Schema::create('billing_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 80)->nullable(); // maternity, surgery, cardiac, etc.
            $table->text('description')->nullable();
            $table->decimal('package_price', 12, 2);
            $table->boolean('includes_room')->default(false);
            $table->boolean('includes_medicines')->default(false);
            $table->boolean('includes_investigations')->default(false);
            $table->boolean('includes_ot')->default(false);
            $table->integer('max_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Line items included in a package (for transparency)
        Schema::create('billing_package_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('billing_packages')->cascadeOnDelete();
            $table->string('item_name');
            $table->string('item_type', 40)->default('service'); // service, medicine, lab, room
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->boolean('is_included')->default(true); // false = extra charge
        });

        // Package applied to an admission
        Schema::create('admission_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('billing_packages')->restrictOnDelete();
            $table->decimal('quoted_price', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'settled', 'cancelled'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_packages');
        Schema::dropIfExists('billing_package_items');
        Schema::dropIfExists('billing_packages');
    }
};
