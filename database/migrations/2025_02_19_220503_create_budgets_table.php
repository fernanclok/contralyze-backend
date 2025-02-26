Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});

Schema::create('budgets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
    $table->decimal('amount', 8, 2);
    $table->timestamps();
});
