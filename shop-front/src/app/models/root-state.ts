import {ProductsStateModel} from '../store/products/products-reducers';
import {CategoriesStateModel} from '../store/categories/categories-reducers';
import {ProductStateModel} from '../store/product/product-reducers';
import {AdminOrdersStateModel} from '../admin/orders/store/admin-orders-reducers';
import {AdminProductsStateModel} from '../admin/products/store/admin-products-reducers';
import {AdminCategoriesStateModel} from '../admin/categories/store/admin-categories-reducers';
import {AdminWarehouseStateModel} from '../admin/warehouse/store/admin-warehouse-reducers';

export interface RootState {
  categoriesReducer: CategoriesStateModel;
  productsReducer: ProductsStateModel;
  productReducer: ProductStateModel;
  adminOrdersReducer: AdminOrdersStateModel;
  adminProductsReducer: AdminProductsStateModel;
  adminCategoriesReducer: AdminCategoriesStateModel;
  adminWarehouseReducer: AdminWarehouseStateModel;
}
