<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // date_format
        DB::statement("update organizations set date_format = 'point-separated-d-m-yyyy' where date_format = 'point-seperated-d-m-yyyy'");
        DB::statement("update organizations set date_format = 'slash-separated-mm-dd-yyyy' where date_format = 'slash-seperated-mm-dd-yyyy'");
        DB::statement("update organizations set date_format = 'slash-separated-dd-mm-yyyy' where date_format = 'slash-seperated-dd-mm-yyyy'");
        DB::statement("update organizations set date_format = 'hyphen-separated-dd-mm-yyyy'where date_format = 'hyphen-seperated-dd-mm-yyyy'");
        DB::statement("update organizations set date_format = 'hyphen-separated-mm-dd-yyyy' where date_format = 'hyphen-seperated-mm-dd-yyyy'");
        DB::statement("update organizations set date_format = 'hyphen-separated-yyyy-mm-dd' where date_format = 'hyphen-seperated-yyyy-mm-dd'");

        // interval_format
        DB::statement("update organizations set interval_format = 'hours-minutes-colon-separated' where interval_format = 'hours-minutes-colon-seperated'");
        DB::statement("update organizations set interval_format = 'hours-minutes-seconds-colon-separated' where interval_format = 'hours-minutes-seconds-colon-seperated'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // date_format
        DB::statement("update organizations set date_format = 'point-seperated-d-m-yyyy' where date_format = 'point-separated-d-m-yyyy'");
        DB::statement("update organizations set date_format = 'slash-seperated-mm-dd-yyyy' where date_format = 'slash-separated-mm-dd-yyyy'");
        DB::statement("update organizations set date_format = 'slash-seperated-dd-mm-yyyy' where date_format = 'slash-separated-dd-mm-yyyy'");
        DB::statement("update organizations set date_format = 'hyphen-seperated-dd-mm-yyyy'where date_format = 'hyphen-separated-dd-mm-yyyy'");
        DB::statement("update organizations set date_format = 'hyphen-seperated-mm-dd-yyyy' where date_format = 'hyphen-separated-mm-dd-yyyy'");
        DB::statement("update organizations set date_format = 'hyphen-seperated-yyyy-mm-dd' where date_format = 'hyphen-separated-yyyy-mm-dd'");

        // interval_format
        DB::statement("update organizations set interval_format = 'hours-minutes-colon-seperated' where interval_format = 'hours-minutes-colon-separated'");
        DB::statement("update organizations set interval_format = 'hours-minutes-seconds-colon-seperated' where interval_format = 'hours-minutes-seconds-colon-separated'");
    }
};
