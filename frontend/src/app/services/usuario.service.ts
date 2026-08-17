import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';
import { User, UsuarioBusqueda } from '../models/crm.models';

@Injectable({ providedIn: 'root' })
export class UsuarioService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient, private auth: AuthService) {}

  listar(): Observable<User[]> {
    return this.http.get<User[]>(`${this.apiUrl}/usuarios/listar.php`, {
      headers: this.auth.getHeaders(),
    });
  }

  crear(datos: { nombre: string; email: string; password: string; rol: string }): Observable<User> {
    return this.http.post<User>(`${this.apiUrl}/usuarios/crear.php`, datos, {
      headers: this.auth.getHeaders(),
    });
  }

  actualizar(id: number, datos: { nombre: string; email: string; rol: string; estado: string }): Observable<User> {
    return this.http.post<User>(`${this.apiUrl}/usuarios/actualizar.php`, { id, ...datos }, {
      headers: this.auth.getHeaders(),
    });
  }

  eliminar(id: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/usuarios/eliminar.php`, { id }, {
      headers: this.auth.getHeaders(),
    });
  }

  // Usado para compartir contenido (Mis Contenidos), no solo para el ABM de admin.
  buscar(termino: string): Observable<UsuarioBusqueda[]> {
    return this.http.get<UsuarioBusqueda[]>(`${this.apiUrl}/usuarios/buscar.php`, {
      headers: this.auth.getHeaders(),
      params: { q: termino },
    });
  }
}
