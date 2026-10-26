import type { StateCreator } from 'zustand';
import type { WorkflowState } from '../state';

export type Slice<T> = StateCreator<WorkflowState, [], [], T>;

