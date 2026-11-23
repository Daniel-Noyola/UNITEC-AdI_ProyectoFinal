# Diagrama Entidad-Relación (ER) — `food_manager`

```mermaid
erDiagram
    usuarios {
        int id_usuario PK "AUTO_INCREMENT"
        varchar nombre "NOT NULL (100)"
        varchar correo UK "NOT NULL (150)"
        varchar contrasena_hash "NOT NULL (255)"
        varchar telefono "NULL (20)"
        enum rol "admin | cliente"
        datetime fecha_registro "DEFAULT CURRENT_TIMESTAMP"
    }

    mesas {
        int id_mesa PK "AUTO_INCREMENT"
        int numero_mesa UK "NOT NULL"
        int capacidad "DEFAULT 4"
        enum estado "disponible | ocupada | inactiva"
    }

    pedidos {
        int id_pedido PK "AUTO_INCREMENT"
        int id_usuario FK "NOT NULL"
        int id_mesa FK "NULL"
        datetime fecha_pedido "DEFAULT CURRENT_TIMESTAMP"
        enum estado "pendiente | preparando | entregado | pagado | cancelado"
        decimal total "DECIMAL(10,2) DEFAULT 0.00"
        datetime fecha_cierre "NULL"
    }

    detalle_pedido {
        int id_detalle PK "AUTO_INCREMENT"
        int id_pedido FK "NOT NULL"
        int id_platillo FK "NOT NULL"
        int cantidad "DEFAULT 1"
        decimal precio_unitario "DECIMAL(10,2) NOT NULL"
        decimal subtotal "DECIMAL(10,2) NOT NULL"
    }

    platillos {
        int id_platillo PK "AUTO_INCREMENT"
        varchar nombre "NOT NULL (120)"
        text descripcion "NULL"
        decimal precio "DECIMAL(10,2) NOT NULL"
        varchar categoria "NOT NULL (60)"
        tinyint disponible "DEFAULT 1"
        varchar imagen "NULL (255)"
    }

    recetas {
        int id_platillo PK,FK "NOT NULL"
        int id_ingrediente PK,FK "NOT NULL"
        decimal cantidad_requerida "DECIMAL(10,2) NOT NULL"
    }

    ingredientes {
        int id_ingrediente PK "AUTO_INCREMENT"
        varchar nombre UK "NOT NULL (100)"
        decimal cantidad_stock "DECIMAL(10,2) DEFAULT 0.00"
        varchar unidad_medida "NOT NULL (30)"
        decimal stock_minimo "DECIMAL(10,2) DEFAULT 0.00"
    }

    usuarios ||--o{ pedidos : "realiza"
    mesas |o--o{ pedidos : "recibe"
    pedidos ||--o{ detalle_pedido : "contiene"
    platillos ||--o{ detalle_pedido : "figura en"
    platillos ||--o{ recetas : "se elabora con"
    ingredientes ||--o{ recetas : "forma parte de"
```

## Resumen de relaciones e integridad referencial

| Relación | Cardinalidad | Clave foránea | Reglas `ON DELETE` / `ON UPDATE` |
| --- | --- | --- | --- |
| `usuarios` → `pedidos` | `1 : N` | `pedidos.id_usuario` | `ON DELETE RESTRICT`, `ON UPDATE CASCADE` |
| `mesas` → `pedidos` | `0..1 : N` | `pedidos.id_mesa` | `ON DELETE SET NULL`, `ON UPDATE CASCADE` |
| `pedidos` → `detalle_pedido` | `1 : N` | `detalle_pedido.id_pedido` | `ON DELETE CASCADE`, `ON UPDATE CASCADE` |
| `platillos` → `detalle_pedido` | `1 : N` | `detalle_pedido.id_platillo` | `ON DELETE RESTRICT`, `ON UPDATE CASCADE` |
| `platillos` → `recetas` | `1 : N` | `recetas.id_platillo` | `ON DELETE CASCADE`, `ON UPDATE CASCADE` |
| `ingredientes` → `recetas` | `1 : N` | `recetas.id_ingrediente` | `ON DELETE RESTRICT`, `ON UPDATE CASCADE` |

> [!NOTE]
> Las tablas `platillos` e `ingredientes` mantienen una relación muchos a muchos (`N : M`) resuelta mediante la tabla intermedia `recetas` con clave primaria compuesta `(id_platillo, id_ingrediente)`.
