import Backbone from 'backbone';

type BackboneModelValue = string | boolean | Backbone.Collection;

export type GetValueCallback = (name: string) => BackboneModelValue;

export type SetValueCallback = (
  name: string,
  value: BackboneModelValue,
) => void;
