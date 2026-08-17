import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Conversaciones } from './conversaciones';

describe('Conversaciones', () => {
  let component: Conversaciones;
  let fixture: ComponentFixture<Conversaciones>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [Conversaciones],
    }).compileComponents();

    fixture = TestBed.createComponent(Conversaciones);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
