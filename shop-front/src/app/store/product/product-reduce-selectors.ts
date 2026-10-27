import {RootState} from '../../models/root-state';

export const productSelector = (state: RootState) => {
  return state.productReducer.productResponse;
}
