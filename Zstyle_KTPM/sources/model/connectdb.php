<?php
/**
 * Docker Database Connection Configuration
 * Use this configuration when running in Docker environment
 */
function pdo_get_connection(){
   // Get database credentials from environment variables or use defaults
   $host = getenv('DB_HOST') ?: 'database';
   $dbname = getenv('DB_NAME') ?: 'zstyle';
   $username = getenv('DB_USER') ?: 'root';
   $password = getenv('DB_PASSWORD') ?: '1234';
   
   $dburl = "mysql:host=$host;dbname=$dbname;charset=utf8";
   
   $conn = new PDO($dburl, $username, $password);
   $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   return $conn;
}

/**
* Thực thi câu lệnh sql thao tác dữ liệu (INSERT, UPDATE, DELETE)
* @param string $sql câu lệnh sql
* @param array $args mảng giá trị cung cấp cho các tham số của $sql
* @throws PDOException lỗi thực thi câu lệnh
*/
function pdo_execute($sql){
   $sql_args = array_slice(func_get_args(), 1);
   try{
       $conn = pdo_get_connection();
       $stmt = $conn->prepare($sql);
       $stmt->execute($sql_args);
   }
   catch(PDOException $e){
       throw $e;
   }
   finally{
       unset($conn);
   }
}

/**
* Thực thi câu lệnh sql truy vấn dữ liệu (SELECT)
* @param string $sql câu lệnh sql
* @param array $args mảng giá trị cung cấp cho các tham số của $sql
* @return array mảng các bản ghi
* @throws PDOException lỗi thực thi câu lệnh
*/
function pdo_query($sql){
   $sql_args = array_slice(func_get_args(), 1);
   try{
       $conn = pdo_get_connection();
       $stmt = $conn->prepare($sql);
       $stmt->execute($sql_args);
       $rows = $stmt->fetchAll();
       return $rows;
   }
   catch(PDOException $e){
       throw $e;
   }
   finally{
       unset($conn);
   }
}

/**
* Thực thi câu lệnh sql truy vấn một bản ghi
* @param string $sql câu lệnh sql
* @param array $args mảng giá trị cung cấp cho các tham số của $sql
* @return array mảng bản ghi
* @throws PDOException lỗi thực thi câu lệnh
*/
function pdo_query_one($sql){
   $sql_args = array_slice(func_get_args(), 1);
   try{
       $conn = pdo_get_connection();
       $stmt = $conn->prepare($sql);
       $stmt->execute($sql_args);
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       return $row;
   }
   catch(PDOException $e){
       throw $e;
   }
   finally{
       unset($conn);
   }
}

/**
* Thực thi câu lệnh sql truy vấn một giá trị
* @param string $sql câu lệnh sql
* @param array $args mảng giá trị cung cấp cho các tham số của $sql
* @return giá trị
* @throws PDOException lỗi thực thi câu lệnh
*/
function pdo_query_value($sql){
   $sql_args = array_slice(func_get_args(), 1);
   try{
       $conn = pdo_get_connection();
       $stmt = $conn->prepare($sql);
       $stmt->execute($sql_args);
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       return array_values($row)[0];
   }
   catch(PDOException $e){
       throw $e;
   }
   finally{
       unset($conn);
   }
}
?>
