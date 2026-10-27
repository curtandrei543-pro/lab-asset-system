<?php
/**
 * Asset.php
 *
 * Main entity class for the Computer Laboratory Asset Management System.
 * Persona: Mark Anthony Cruz, Computer Laboratory Custodian.
 *
 * Maps directly onto the "Assets" table from the ERD:
 *   Asset_id, Asset_name, Brand, Serial_Number, Purchase_Date,
 *   Status, Category_id (FK -> Asset_Categories), Laboratory_id (FK -> Laboratories)
 */

class Asset
{
    private PDO $conn;
    private string $table = "Assets";

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Insert a new asset record.
     *
     * @param array $data Keys: Asset_name, Brand, Serial_Number, Purchase_Date,
     *                     Status, Category_id, Laboratory_id
     * @return array ['success' => bool, 'message' => string, 'id' => int|null]
     */
    /**
     * TRANSACTION 1: add asset + audit log entry (both succeed or both roll back).
     */
    public function addAsset(array $data, ?int $userId = null): array
    {
        $sql = "INSERT INTO {$this->table}
                    (Asset_name, Brand, Serial_Number, Purchase_Date, Status, Category_id, Laboratory_id)
                VALUES
                    (:asset_name, :brand, :serial_number, :purchase_date, :status, :category_id, :laboratory_id)";

        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare($sql);

            $stmt->bindValue(":asset_name", $data['Asset_name']);
            $stmt->bindValue(":brand", $data['Brand']);
            $stmt->bindValue(":serial_number", $data['Serial_Number'], PDO::PARAM_INT);
            $stmt->bindValue(":purchase_date", $data['Purchase_Date']);
            $stmt->bindValue(":status", $data['Status']);
            $stmt->bindValue(":category_id", $data['Category_id'], PDO::PARAM_INT);
            $stmt->bindValue(":laboratory_id", $data['Laboratory_id'], PDO::PARAM_INT);

            $stmt->execute();
            $newId = (int)$this->conn->lastInsertId();

            $this->logAudit("ADD", $newId,
                "Added {$data['Asset_name']} ({$data['Brand']}, S/N {$data['Serial_Number']})", $userId);

            $this->conn->commit();

            return ["success" => true, "message" => "Asset added successfully.", "id" => $newId];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) { $this->conn->rollBack(); }
            return ["success" => false, "message" => "Failed to add asset (rolled back): " . $e->getMessage(), "id" => null];
        }
    }

    /**
     * Fetch all asset records, joined with category and laboratory names
     * for readable display.
     *
     * @return array List of asset rows (empty array on failure).
     */
    public function getAssets(): array
    {
        $sql = "SELECT a.Asset_id, a.Asset_name, a.Brand, a.Serial_Number,
                       a.Purchase_Date, a.Status,
                       a.Category_id, c.Category_Name,
                       a.Laboratory_id, l.Laboratrory_Name
                FROM {$this->table} a
                INNER JOIN Asset_Categories c ON a.Category_id = c.Category_id
                INNER JOIN Laboratories l ON a.Laboratory_id = l.Laboratory_id
                ORDER BY a.Asset_id DESC";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getAssets failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch a single asset by its ID (used to pre-fill the update form).
     */
    public function getAssetById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE Asset_id = :id LIMIT 1";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("getAssetById failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update an existing asset's details.
     *
     * @param int   $id   The Asset_id to update.
     * @param array $data Same keys as addAsset().
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateAsset(int $id, array $data): array
    {
        $sql = "UPDATE {$this->table}
                SET Asset_name = :asset_name,
                    Brand = :brand,
                    Serial_Number = :serial_number,
                    Purchase_Date = :purchase_date,
                    Status = :status,
                    Category_id = :category_id,
                    Laboratory_id = :laboratory_id
                WHERE Asset_id = :id";

        try {
            $stmt = $this->conn->prepare($sql);

            $stmt->bindValue(":asset_name", $data['Asset_name']);
            $stmt->bindValue(":brand", $data['Brand']);
            $stmt->bindValue(":serial_number", $data['Serial_Number'], PDO::PARAM_INT);
            $stmt->bindValue(":purchase_date", $data['Purchase_Date']);
            $stmt->bindValue(":status", $data['Status']);
            $stmt->bindValue(":category_id", $data['Category_id'], PDO::PARAM_INT);
            $stmt->bindValue(":laboratory_id", $data['Laboratory_id'], PDO::PARAM_INT);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);

            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                return ["success" => false, "message" => "No asset found with that ID, or no changes were made."];
            }
            return ["success" => true, "message" => "Asset updated successfully."];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Failed to update asset: " . $e->getMessage()];
        }
    }

    /**
     * TRANSACTION 3: delete an asset together with its assignment and
     * maintenance records, then write the audit entry. If any step fails,
     * everything is rolled back and nothing is deleted.
     */
    public function deleteAsset(int $id, ?int $userId = null): array
    {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("SELECT Asset_name, Serial_Number FROM {$this->table} WHERE Asset_id = :id FOR UPDATE");
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();
            if (!$row) {
                throw new Exception("No asset found with that ID.");
            }

            $m = $this->conn->prepare("DELETE FROM Maintenance_Records WHERE Asset_id = :id");
            $m->bindValue(":id", $id, PDO::PARAM_INT);
            $m->execute();
            $maintenance = $m->rowCount();

            $a = $this->conn->prepare("DELETE FROM Asset_Assignment WHERE Asset_id = :id");
            $a->bindValue(":id", $id, PDO::PARAM_INT);
            $a->execute();
            $assignments = $a->rowCount();

            $d = $this->conn->prepare("DELETE FROM {$this->table} WHERE Asset_id = :id");
            $d->bindValue(":id", $id, PDO::PARAM_INT);
            $d->execute();

            $this->logAudit("DELETE", $id,
                "Deleted {$row['Asset_name']} (S/N {$row['Serial_Number']}) with {$assignments} assignment and {$maintenance} maintenance record(s)",
                $userId);

            $this->conn->commit();
            return ["success" => true, "message" => "Asset deleted successfully."];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) { $this->conn->rollBack(); }
            return ["success" => false, "message" => "Delete failed (rolled back): " . $e->getMessage()];
        }
    }

    /**
     * TRANSACTION 2: transfer an asset to another laboratory.
     * Updates Assets, inserts an Asset_Assignment record and an audit entry
     * as one unit of work.
     */
    public function transferAsset(int $id, int $newLabId, string $remarks, ?int $userId = null): array
    {
        try {
            $this->conn->beginTransaction();

            // Lock the asset row so nobody else changes it mid-transfer
            $stmt = $this->conn->prepare("SELECT Asset_name, Status, Laboratory_id FROM {$this->table} WHERE Asset_id = :id FOR UPDATE");
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();
            if (!$row) {
                throw new Exception("Asset not found.");
            }
            if ($row["Status"] === "Retired") {
                throw new Exception("Retired assets cannot be transferred.");
            }
            if ((int)$row["Laboratory_id"] === $newLabId) {
                throw new Exception("The asset is already in that laboratory.");
            }

            $labStmt = $this->conn->prepare("SELECT Laboratory_id, Laboratrory_Name FROM Laboratories WHERE Laboratory_id IN (:old, :new)");
            $labStmt->bindValue(":old", (int)$row["Laboratory_id"], PDO::PARAM_INT);
            $labStmt->bindValue(":new", $newLabId, PDO::PARAM_INT);
            $labStmt->execute();
            $labs = [];
            foreach ($labStmt->fetchAll() as $l) { $labs[(int)$l["Laboratory_id"]] = $l["Laboratrory_Name"]; }
            if (!isset($labs[$newLabId])) {
                throw new Exception("Destination laboratory does not exist.");
            }
            $oldName = $labs[(int)$row["Laboratory_id"]] ?? "unknown";

            // Step 1: move the asset
            $up = $this->conn->prepare("UPDATE {$this->table} SET Laboratory_id = :lab WHERE Asset_id = :id");
            $up->bindValue(":lab", $newLabId, PDO::PARAM_INT);
            $up->bindValue(":id", $id, PDO::PARAM_INT);
            $up->execute();

            // Step 2: record the assignment
            $remarks = mb_substr(trim($remarks) !== "" ? trim($remarks) : "Transferred", 0, 45);
            $as = $this->conn->prepare("INSERT INTO Asset_Assignment (assigned_id, Remarks, Laboratory_id, Asset_id)
                                        VALUES (:assigned, :remarks, :lab, :asset)");
            $as->bindValue(":assigned", $userId ?? 0, PDO::PARAM_INT);
            $as->bindValue(":remarks", $remarks);
            $as->bindValue(":lab", $newLabId, PDO::PARAM_INT);
            $as->bindValue(":asset", $id, PDO::PARAM_INT);
            $as->execute();

            // Step 3: audit trail
            $this->logAudit("TRANSFER", $id,
                "Moved {$row['Asset_name']} from {$oldName} to {$labs[$newLabId]}", $userId);

            $this->conn->commit();
            return ["success" => true, "message" => "{$row['Asset_name']} transferred to {$labs[$newLabId]}."];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) { $this->conn->rollBack(); }
            return ["success" => false, "message" => "Transfer failed (rolled back): " . $e->getMessage()];
        }
    }

    /** Writes one audit row. Must be called inside an open transaction. */
    private function logAudit(string $action, ?int $assetId, string $details, ?int $userId): void
    {
        $stmt = $this->conn->prepare("INSERT INTO asset_audit_log (action, asset_id, details, user_id)
                                      VALUES (:action, :asset_id, :details, :user_id)");
        $stmt->bindValue(":action", $action);
        $stmt->bindValue(":asset_id", $assetId, $assetId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(":details", mb_substr($details, 0, 255));
        $stmt->bindValue(":user_id", $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
    }

    /** Latest audit entries with the name of the user who did it. */
    public function getAuditLog(int $limit = 50): array
    {
        $sql = "SELECT l.id, l.action, l.asset_id, l.details, l.created_at, u.full_name
                FROM asset_audit_log l
                LEFT JOIN auth_users u ON u.id = l.user_id
                ORDER BY l.id DESC LIMIT " . (int)$limit;
        try {
            return $this->conn->query($sql)->fetchAll();
        } catch (PDOException $e) {
            error_log("getAuditLog failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search assets by any field: name, brand, serial number, status,
     * category name, or laboratory name.
     *
     * @param string $keyword
     * @return array List of matching asset rows.
     */
    public function searchAssets(string $keyword): array
    {
        $sql = "SELECT a.Asset_id, a.Asset_name, a.Brand, a.Serial_Number,
                       a.Purchase_Date, a.Status,
                       a.Category_id, c.Category_Name,
                       a.Laboratory_id, l.Laboratrory_Name
                FROM {$this->table} a
                INNER JOIN Asset_Categories c ON a.Category_id = c.Category_id
                INNER JOIN Laboratories l ON a.Laboratory_id = l.Laboratory_id
                WHERE a.Asset_name        LIKE :kw
                   OR a.Brand             LIKE :kw
                   OR a.Serial_Number     LIKE :kw
                   OR a.Status            LIKE :kw
                   OR c.Category_Name     LIKE :kw
                   OR l.Laboratrory_Name  LIKE :kw
                ORDER BY a.Asset_id DESC";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(":kw", "%{$keyword}%");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("searchAssets failed: " . $e->getMessage());
            return [];
        }
    }
}
