import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';
import { Client } from '../models/crm.models';

@Injectable({ providedIn: 'root' })
export class ClienteService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient, private auth: AuthService) {}

  listar(): Observable<Client[]> {
    return this.http.get<Client[]>(`${this.apiUrl}/clientes/listar.php`, {
      headers: this.auth.getHeaders(),
    });
  }

  crear(datos: { nombre: string; email: string; telefono: string; empresa: string; ubicacion: string }): Observable<Client> {
    return this.http.post<Client>(`${this.apiUrl}/clientes/crear.php`, datos, {
      headers: this.auth.getHeaders(),
    });
  }

  actualizar(id: number, datos: { nombre: string; email: string; telefono: string; empresa: string; ubicacion: string }): Observable<Client> {
    return this.http.post<Client>(`${this.apiUrl}/clientes/actualizar.php`, { id, ...datos }, {
      headers: this.auth.getHeaders(),
    });
  }

  eliminar(id: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/clientes/eliminar.php`, { id }, {
      headers: this.auth.getHeaders(),
    });
  }
}
