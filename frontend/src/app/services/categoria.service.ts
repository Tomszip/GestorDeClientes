import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';
import { Categoria } from '../models/crm.models';

@Injectable({ providedIn: 'root' })
export class CategoriaService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient, private auth: AuthService) {}

  listar(): Observable<Categoria[]> {
    return this.http.get<Categoria[]>(`${this.apiUrl}/categorias/listar.php`, {
      headers: this.auth.getHeaders(),
    });
  }

  crear(datos: { nombre: string; descripcion: string }): Observable<Categoria> {
    return this.http.post<Categoria>(`${this.apiUrl}/categorias/crear.php`, datos, {
      headers: this.auth.getHeaders(),
    });
  }

  actualizar(id: number, datos: { nombre: string; descripcion: string }): Observable<Categoria> {
    return this.http.post<Categoria>(`${this.apiUrl}/categorias/actualizar.php`, { id, ...datos }, {
      headers: this.auth.getHeaders(),
    });
  }

  eliminar(id: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/categorias/eliminar.php`, { id }, {
      headers: this.auth.getHeaders(),
    });
  }
}
