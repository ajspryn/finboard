CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "pembiayaans"(
  "id" integer primary key autoincrement not null,
  "nokontrak" varchar not null,
  "nama" varchar not null,
  "tgleff" date,
  "jw" integer,
  "tglexp" date,
  "mdlawal" numeric not null default '0',
  "mgnawal" numeric not null default '0',
  "osmdlc" numeric not null default '0',
  "osmgnc" numeric not null default '0',
  "colbaru" varchar,
  "kdaoh" varchar,
  "acpok" varchar,
  "angsmdl" numeric not null default '0',
  "angsmgn" numeric not null default '0',
  "alamat" text,
  "telprmh" varchar,
  "hp" varchar,
  "fnama" varchar,
  "sahirrp" numeric not null default '0',
  "tgkpok" numeric not null default '0',
  "tgkmgn" numeric not null default '0',
  "tgkdnd" numeric not null default '0',
  "blntgkpok" integer,
  "blntgkmgn" integer,
  "blntgkdnd" integer,
  "kdkolek" varchar,
  "kdgroupdeb" varchar,
  "kdgroupdana" varchar,
  "haritgkmdl" integer not null default '0',
  "haritgkmgn" integer not null default '0',
  "nocif" varchar,
  "kdprd" varchar,
  "pokpby" varchar,
  "kdloc" varchar,
  "kelurahan" varchar,
  "kecamatan" varchar,
  "kota" varchar,
  "nmao" varchar,
  "colllanjut" varchar,
  "tgkharilanjut" integer not null default '0',
  "angs_ke" integer not null default '0',
  "tagmdl" numeric not null default '0',
  "tagmgn" numeric not null default '0',
  "inptgl" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "angske_x" integer,
  "kdmco" varchar,
  "kdsektor" varchar,
  "kdsub" varchar,
  "plafon" numeric,
  "period_month" varchar,
  "period_year" varchar
);
CREATE INDEX "pembiayaans_period_year_period_month_index" on "pembiayaans"(
  "period_year",
  "period_month"
);
CREATE UNIQUE INDEX "pembiayaans_nokontrak_period_unique" on "pembiayaans"(
  "nokontrak",
  "period_year",
  "period_month"
);
CREATE TABLE IF NOT EXISTS "depositos"(
  "id" integer primary key autoincrement not null,
  "nodep" varchar not null,
  "nocif" varchar,
  "nobilyet" varchar,
  "nama" varchar,
  "nomrp" numeric not null default '0',
  "tax" numeric default '0',
  "bnghtg" numeric default '0',
  "nisbahrp" numeric default '0',
  "nisbah" numeric default '0',
  "spread" numeric default '0',
  "equivrate" numeric default '0',
  "komitrate" numeric default '0',
  "kdprd" varchar,
  "jkwaktu" varchar,
  "jnsjkwaktu" varchar,
  "aro" varchar,
  "stsrec" varchar,
  "ststrn" varchar,
  "stspep" varchar,
  "kdrisk" varchar,
  "stskait" varchar,
  "golcustbi" varchar,
  "tglbuka" date,
  "tgleff" date,
  "tgljtempo" date,
  "tgllhr" date,
  "kdwil" varchar,
  "kodeaoh" varchar,
  "kodeaop" varchar,
  "alamat" varchar,
  "kota" varchar,
  "kelurahan" varchar,
  "kecamatan" varchar,
  "kdpos" varchar,
  "noid" varchar,
  "telprmh" varchar,
  "hp" varchar,
  "nmibu" varchar,
  "noacbng" varchar,
  "tambahnom" varchar,
  "ketsandi" varchar,
  "namapt" varchar,
  "period_month" varchar not null,
  "period_year" integer not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "depositos_nodep_period_year_period_month_index" on "depositos"(
  "nodep",
  "period_year",
  "period_month"
);
CREATE INDEX "depositos_period_year_period_month_index" on "depositos"(
  "period_year",
  "period_month"
);
CREATE INDEX "depositos_stsrec_index" on "depositos"("stsrec");
CREATE INDEX "depositos_tgljtempo_index" on "depositos"("tgljtempo");
CREATE INDEX "depositos_nodep_index" on "depositos"("nodep");
CREATE INDEX "depositos_nocif_index" on "depositos"("nocif");
CREATE TABLE IF NOT EXISTS "tabungans"(
  "id" integer primary key autoincrement not null,
  "notab" varchar not null,
  "nocif" varchar,
  "kodeprd" varchar,
  "fnama" varchar,
  "namaqq" varchar,
  "sahirrp" numeric not null default '0',
  "saldoblok" numeric not null default '0',
  "tax" numeric default '0',
  "avgeom" numeric default '0',
  "stsrec" varchar,
  "stsrest" varchar,
  "stspep" varchar,
  "kdrisk" varchar,
  "tgltrnakh" date,
  "tgllhr" date,
  "noid" varchar,
  "hp" varchar,
  "nmibu" varchar,
  "ketsandi" varchar,
  "namapt" varchar,
  "kodeloc" varchar,
  "period_month" varchar not null,
  "period_year" integer not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "tabungans_notab_period_year_period_month_index" on "tabungans"(
  "notab",
  "period_year",
  "period_month"
);
CREATE INDEX "tabungans_period_year_period_month_index" on "tabungans"(
  "period_year",
  "period_month"
);
CREATE INDEX "tabungans_stsrec_index" on "tabungans"("stsrec");
CREATE INDEX "tabungans_notab_index" on "tabungans"("notab");
CREATE INDEX "tabungans_nocif_index" on "tabungans"("nocif");
CREATE TABLE IF NOT EXISTS "email_pin_codes"(
  "id" integer primary key autoincrement not null,
  "email" varchar not null,
  "pin_code" varchar not null,
  "expires_at" datetime not null,
  "used_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "email_pin_codes_email_expires_at_index" on "email_pin_codes"(
  "email",
  "expires_at"
);
CREATE INDEX "email_pin_codes_email_index" on "email_pin_codes"("email");
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "role" varchar not null default('admin')
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "linkages"(
  "id" integer primary key autoincrement not null,
  "nokontrak" varchar not null,
  "nocif" varchar,
  "nama" varchar not null,
  "tgleff" date,
  "tgljt" date,
  "kelompok" varchar,
  "jnsakad" varchar,
  "prsnisbah" numeric not null default '0',
  "plafon" numeric not null default '0',
  "os" numeric not null default '0',
  "period_month" integer,
  "period_year" integer,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "linkages_nokontrak_unique" on "linkages"("nokontrak");

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_11_11_053545_create_pembiayaans_table',2);
INSERT INTO migrations VALUES(5,'2025_11_11_061408_add_missing_columns_to_pembiayaans_table',3);
INSERT INTO migrations VALUES(6,'2025_11_11_082034_add_period_to_pembiayaans_table',4);
INSERT INTO migrations VALUES(7,'2025_11_11_083044_update_pembiayaans_unique_constraint',5);
INSERT INTO migrations VALUES(10,'2025_11_13_055439_create_depositos_table',6);
INSERT INTO migrations VALUES(11,'2025_11_13_055439_create_tabungans_table',6);
INSERT INTO migrations VALUES(12,'2025_11_17_094501_create_email_pin_codes_table',7);
INSERT INTO migrations VALUES(13,'2025_11_17_094515_add_role_to_users_table',7);
INSERT INTO migrations VALUES(14,'2025_11_17_104545_make_password_nullable_in_users_table',8);
INSERT INTO migrations VALUES(15,'2025_11_17_104649_drop_password_column_from_users_table',9);
INSERT INTO migrations VALUES(16,'2025_11_19_074232_add_linkage_to_funding_tables',10);
INSERT INTO migrations VALUES(17,'2025_11_19_080912_add_linkage_to_pembiayaans_table',11);
INSERT INTO migrations VALUES(18,'2025_11_19_081539_create_linkages_table',12);
INSERT INTO migrations VALUES(19,'2025_11_19_083339_remove_linkage_from_pembiayaans_table',13);
INSERT INTO migrations VALUES(20,'2025_11_19_083800_add_linkage_back_to_pembiayaans_table',14);
INSERT INTO migrations VALUES(21,'2025_11_19_093000_drop_sumber_dana_from_linkages_table',15);
INSERT INTO migrations VALUES(22,'2025_11_19_093002_drop_sumber_dana_from_linkages_table',15);
INSERT INTO migrations VALUES(23,'2025_11_19_130447_remove_linkage_columns_from_funding_tables',16);
