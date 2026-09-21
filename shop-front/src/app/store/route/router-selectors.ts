import { createFeatureSelector, createSelector } from '@ngrx/store';
import { RouterReducerState } from '@ngrx/router-store';
import { RouterStateUrl } from './router-sate-serializer';

const getRouterState = createFeatureSelector<RouterReducerState<RouterStateUrl>>('router');

export const routerSelector = createSelector(getRouterState, (state) => state);

export const routerStateUrlSelector = createSelector(
  getRouterState,
  (state) => {
    console.log({state});
    state?.state
  }
);
