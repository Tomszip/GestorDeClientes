import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthRespuesta, User } from '../models/crm.models';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  login(email: string, password: string): Observable<AuthRespuesta> {
    return this.http.post<AuthRespuesta>(`${this.apiUrl}/auth/login.php`, { email, password });
  }

  registrar(nombre: string, email: string, password: string): Observable<AuthRespuesta> {
    return this.http.post<AuthRespuesta>(`${this.apiUrl}/auth/registro.php`, { nombre, email, password });
  }

  guardarSesion(respuesta: AuthRespuesta) {
    localStorage.setItem('token', respuesta.token);
    localStorage.setItem('user', JSON.stringify(respuesta.user));
  }

  logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  }

  getUsuarioActual(): User | null {
    const stored = localStorage.getItem('user');
    return stored ? JSON.parse(stored) : null;
  }

  estaAutenticado(): boolean {
    return !!localStorage.getItem('token');
  }

  // Cabecera de autenticación que necesitan todos los endpoints protegidos.
  // Centralizarla acá evita repetirla en cada servicio de datos.
  getHeaders(): HttpHeaders {
    const token = localStorage.getItem('token');
    return new HttpHeaders({ Authorization: `Bearer ${token}` });
  }
}
