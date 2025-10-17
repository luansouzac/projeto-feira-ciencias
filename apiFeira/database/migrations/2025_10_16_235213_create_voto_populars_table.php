    <?php
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
        {
            Schema::create('votos_populares', function (Blueprint $table) {
                $table->id('id_voto');
                $table->unsignedBigInteger('id_projeto');
                $table->unsignedBigInteger('id_usuario');
    
                $table->timestamps();

                $table->foreign('id_projeto')->references('id_projeto')->on('projetos')->onDelete('cascade');
                $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');

                $table->unique(['id_projeto', 'id_usuario']);
            });
        }
        public function down(): void
        {
            Schema::dropIfExists('votos_populares');
        }
    };
