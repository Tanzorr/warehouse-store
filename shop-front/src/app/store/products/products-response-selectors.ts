import {RootState} from '../../models/root-state';

export const productsResponseSelector = (state: RootState) => {
  return state.productsReducer.productsResponse;
}
