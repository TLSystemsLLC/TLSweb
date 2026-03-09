<?php

use App\Support\TenantRegistry;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Procedure definition
$sql = "
CREATE OR ALTER PROCEDURE [dbo].[webUserSearch]
(
    @Search     nvarchar(200) = NULL,
    @Page       int = 1,
    @PageSize   int = 100
)
AS
BEGIN
    SET NOCOUNT ON;

    -- ----------------------------------------------------------------
    -- Sentinel: '*' = no filter; row limits still apply.
    -- ----------------------------------------------------------------
    DECLARE @ReturnAll bit = 0;
    IF LTRIM(RTRIM(@Search)) = N'*'
    BEGIN
        SET @ReturnAll = 1;
        SET @Search    = NULL;
    END

    SET @Search = NULLIF(LTRIM(RTRIM(@Search)), N'');

    -- Page number: must be >= 1
    IF @Page IS NULL OR @Page < 1 SET @Page = 1;

    -- Page size caps:
    --   Filtered    → max 500 rows/page
    --   Unfiltered  → max 1,000 rows/page
    DECLARE @MaxPageSize int = CASE WHEN @ReturnAll = 1 THEN 1000 ELSE 500 END;

    IF @PageSize IS NULL OR @PageSize < 1  SET @PageSize = 100;
    IF @PageSize > @MaxPageSize            SET @PageSize = @MaxPageSize;

    -- ----------------------------------------------------------------
    -- Build LIKE pattern
    -- ----------------------------------------------------------------
    DECLARE @Like nvarchar(450) = NULL;

    IF @Search IS NOT NULL
    BEGIN
        DECLARE @Esc nvarchar(200) =
            REPLACE(REPLACE(@Search, N'%', N'[%]'), N'_', N'[_]');

        SET @Like = N'%' + @Esc + N'%';
    END

    -- ----------------------------------------------------------------
    -- CTE computes TotalRows once; outer query reuses it for
    -- TotalPages and HasNextPage without a second COUNT pass.
    -- ----------------------------------------------------------------
    ;WITH cte AS
    (
        SELECT
            u.UserKey,
            u.UserID,
            u.UserName,
            u.Email,
            u.Active,
            u.UserType,
            COUNT(*) OVER () AS TotalRows
        FROM  dbo.tUser AS u
        WHERE
            @Like IS NULL
            OR u.UserName LIKE @Like
            OR u.UserID   LIKE @Like
            OR u.Email    LIKE @Like
    )
    SELECT
        UserKey,
        UserID,
        UserName,
        Email,
        Active,
        UserType,
        TotalRows,
        @Page                                                       AS CurrentPage,
        @PageSize                                                   AS PageSize,
        CEILING(CAST(TotalRows AS float) / @PageSize)              AS TotalPages,
        CASE WHEN TotalRows > @Page * @PageSize THEN 1 ELSE 0 END  AS HasNextPage
    FROM  cte
    ORDER BY UserName
    OFFSET (@Page - 1) * @PageSize ROWS
    FETCH NEXT @PageSize ROWS ONLY;

END
";

$tenants = array_keys(TenantRegistry::allowedTenants());
echo "Found " . count($tenants) . " tenants: " . implode(', ', $tenants) . PHP_EOL;

$dsn  = env('TLS_ODBC_DSN');
$user = env('TLS_SQL_USER');
$pass = env('TLS_SQL_PASS');

try {
    $pdo = new PDO("odbc:$dsn", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("ODBC connect failed: " . $e->getMessage() . PHP_EOL);
}

foreach ($tenants as $tenant) {
    echo "Deploying to tenant: $tenant... ";
    try {
        // Use the tenant DB
        $pdo->exec("USE [$tenant]");
        $pdo->exec($sql);
        echo "OK" . PHP_EOL;
    } catch (PDOException $e) {
        echo "FAILED: " . $e->getMessage() . PHP_EOL;
    }
}

echo "Done." . PHP_EOL;
